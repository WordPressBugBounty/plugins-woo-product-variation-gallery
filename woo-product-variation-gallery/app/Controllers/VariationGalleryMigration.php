<?php
/**
 * Batched migration of the legacy variation gallery meta into WooCommerce's
 * native per-variation gallery.
 *
 * @package Rtwpvg
 */

namespace Rtwpvg\Controllers;

use Rtwpvg\Helpers\Functions;

defined( 'ABSPATH' ) || exit;

/**
 * Copies `rtwpvg_images` into WooCommerce's `_product_image_gallery` variation
 * meta so the native gallery becomes the single source of truth on
 * WooCommerce 11.1.0+.
 *
 * The legacy meta is deliberately left in place: it keeps CSV export/import and
 * any third-party reader working, and lets a store roll back to an older
 * WooCommerce without losing data. A sentinel meta marks each processed
 * variation so the legacy value is never used to resurrect removed images.
 */
class VariationGalleryMigration {

	/**
	 * Option recording when the migration finished.
	 *
	 * @var string
	 */
	const COMPLETED_OPTION = 'rtwpvg_native_gallery_migration_completed_at';

	/**
	 * Action Scheduler hook running a single batch.
	 *
	 * @var string
	 */
	const BATCH_HOOK = 'rtwpvg_migrate_variation_gallery_batch';

	/**
	 * Action Scheduler group.
	 *
	 * @var string
	 */
	const BATCH_GROUP = 'rtwpvg-variation-gallery';

	/**
	 * Maximum number of variations processed per batch.
	 *
	 * An upper bound, not a fixed cost: `run_batch()` also stops on its time
	 * budget, so a slow host simply completes fewer than this per batch.
	 *
	 * @var int
	 */
	const BATCH_SIZE = 250;

	/**
	 * How many times a single variation is attempted before it is retired.
	 *
	 * @var int
	 */
	const MAX_ATTEMPTS = 3;

	/**
	 * Post meta counting migration attempts for one variation.
	 *
	 * @var string
	 */
	const ATTEMPTS_META_KEY = '_rtwpvg_gallery_migration_attempts';

	/**
	 * Transient throttling the "is a batch already queued" lookup.
	 *
	 * @var string
	 */
	const SCHEDULE_CHECK_TRANSIENT = 'rtwpvg_gallery_migration_scheduled';

	/**
	 * WooCommerce log source for migration failures.
	 *
	 * @var string
	 */
	const LOG_SOURCE = 'rtwpvg-variation-gallery-migration';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( self::BATCH_HOOK, array( __CLASS__, 'run_batch' ) );
		// Action Scheduler is only available from `init`.
		add_action( 'init', array( $this, 'maybe_schedule' ), 21 );
	}

	/**
	 * Queue the next batch unless the migration is done or already queued.
	 *
	 * @return void
	 */
	public function maybe_schedule() {
		if ( ! Functions::has_native_variation_gallery() || get_option( self::COMPLETED_OPTION ) ) {
			return;
		}

		// This runs on every request until the migration finishes, and the lookup
		// below is a database query. One check a minute is enough to keep the chain
		// alive while costing nothing on a busy store.
		if ( get_transient( self::SCHEDULE_CHECK_TRANSIENT ) ) {
			return;
		}

		if ( self::has_queued_batch() ) {
			set_transient( self::SCHEDULE_CHECK_TRANSIENT, 1, MINUTE_IN_SECONDS );

			return;
		}

		$queue = self::get_queue();

		if ( ! $queue ) {
			// Action Scheduler is not loaded yet (or at all). Leave the throttle
			// unset so the next request re-checks instead of waiting a minute.
			return;
		}

		$queue->add( self::BATCH_HOOK, array(), self::BATCH_GROUP );

		set_transient( self::SCHEDULE_CHECK_TRANSIENT, 1, MINUTE_IN_SECONDS );
	}

	/**
	 * Whether a batch is already pending or currently running.
	 *
	 * `WC_Queue_Interface::get_next()` cannot answer this. `as_next_scheduled_action()`
	 * returns boolean `true` for a RUNNING action and a timestamp for a PENDING one,
	 * but `WC_Action_Queue::get_next()` only converts numeric returns and hands back
	 * `null` for everything else — so a running batch reads as "nothing queued" and
	 * every request during it would enqueue a duplicate.
	 *
	 * A failed action counts as absent on purpose: that is what lets a broken chain
	 * restart on the next request.
	 *
	 * @return bool
	 */
	protected static function has_queued_batch() {
		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			return false;
		}

		return false !== as_next_scheduled_action( self::BATCH_HOOK, array(), self::BATCH_GROUP );
	}

	/**
	 * Migrate one batch of variations and queue the next one when work remains.
	 *
	 * @return void
	 */
	public static function run_batch() {
		if ( ! Functions::has_native_variation_gallery() || get_option( self::COMPLETED_OPTION ) ) {
			return;
		}

		$variation_ids = self::get_pending_variation_ids( self::BATCH_SIZE );
		$deadline      = self::get_deadline();
		$memory_cap    = self::get_memory_cap();
		$processed     = 0;

		foreach ( $variation_ids as $variation_id ) {
			// Always take at least one, otherwise a budget that has already been spent
			// would queue an endless chain of batches that migrate nothing.
			if ( $processed > 0 && ( microtime( true ) >= $deadline || memory_get_usage( true ) >= $memory_cap ) ) {
				break;
			}

			self::migrate_variation( $variation_id );
			++$processed;

			// Each read pulls the variation and its parent into WordPress's in-memory
			// object cache, which nothing in a batch loop ever evicts. Left alone it
			// grows for the whole batch; on a store with large galleries that is what
			// reaches the memory limit. Dropping each row once it is migrated keeps a
			// batch's footprint flat regardless of BATCH_SIZE.
			self::free_variation_cache( $variation_id );
		}

		if ( self::get_pending_variation_ids( 1 ) ) {
			$queue = self::get_queue();

			if ( $queue ) {
				$queue->add( self::BATCH_HOOK, array(), self::BATCH_GROUP );
			}

			return;
		}

		if ( ! get_option( self::COMPLETED_OPTION ) ) {
			update_option( self::COMPLETED_OPTION, time(), false );
		}
	}

	/**
	 * Copy one variation's legacy gallery into the native gallery.
	 *
	 * The native value wins when it already holds images, so a merchant who has
	 * authored through WooCommerce before the migration ran is never overwritten.
	 *
	 * @param int $variation_id Variation ID.
	 *
	 * @return void
	 */
	protected static function migrate_variation( $variation_id ) {
		$variation_id = absint( $variation_id );

		if ( ! $variation_id ) {
			return;
		}

		/*
		 * Count the attempt BEFORE doing any work.
		 *
		 * A PHP fatal — out of memory, max_execution_time, a third-party hook on the
		 * variation save — terminates the process outright: no catch block runs and no
		 * shutdown handler of ours gets to record anything. A counter written afterwards
		 * would therefore never survive on precisely the row that keeps killing the
		 * batch, and that row would be re-selected forever. Written first it persists,
		 * and get_pending_variation_ids() stops selecting it once MAX_ATTEMPTS is hit.
		 */
		$attempts = (int) get_post_meta( $variation_id, self::ATTEMPTS_META_KEY, true ) + 1;
		update_post_meta( $variation_id, self::ATTEMPTS_META_KEY, $attempts );

		if ( $attempts > 1 ) {
			self::log(
				sprintf(
					'Variation %d: attempt %d of %d (a previous attempt did not complete).',
					$variation_id,
					$attempts,
					self::MAX_ATTEMPTS
				)
			);
		}

		try {
			$variation = wc_get_product( $variation_id );

			if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
				// wc_get_product() can fail on a row the batch query still matches — an
				// orphaned or corrupt variation. Nothing here will ever migrate, so retire
				// it immediately rather than burning MAX_ATTEMPTS passes on it.
				self::retire_variation( $variation_id, 'not a loadable product variation' );

				return;
			}

			/*
			 * Re-check ownership at write time, not just in the batch query.
			 *
			 * A batch selects up to BATCH_SIZE ids in one statement and then processes
			 * them one by one, so a merchant can save this variation in between. Were they
			 * to empty its gallery in that window, the native store would be legitimately
			 * empty while the legacy meta still held the old list — and the write below
			 * would put the removed images straight back.
			 */
			if ( Functions::is_native_gallery_owned( $variation_id ) ) {
				delete_post_meta( $variation_id, self::ATTEMPTS_META_KEY );

				return;
			}

			$legacy_ids = array_values( array_filter( array_map( 'absint', (array) get_post_meta( $variation_id, Functions::LEGACY_GALLERY_META_KEY, true ) ) ) );
			$native_ids = Functions::get_native_variation_gallery_ids( $variation );

			if ( empty( $native_ids ) && ! empty( $legacy_ids ) ) {
				$variation->set_gallery_image_ids( $legacy_ids );
				$variation->save();
			}

			update_post_meta( $variation_id, Functions::NATIVE_GALLERY_SENTINEL_META_KEY, 'yes' );

			// The cached props were built from the legacy list; drop them so the next
			// read rebuilds from the native gallery.
			Functions::delete_transients( $variation_id, 'variation' );

			// Migrated: the sentinel now excludes this row, so the counter is dead weight.
			delete_post_meta( $variation_id, self::ATTEMPTS_META_KEY );
		} catch ( \Throwable $e ) {
			/*
			 * Caught per variation, not per batch: an exception raised by one variation
			 * (or by a third-party hook on its save) must not abandon the other 249 rows
			 * the batch already selected.
			 */
			self::log(
				sprintf( 'Variation %d failed on attempt %d: %s', $variation_id, $attempts, $e->getMessage() ),
				'error'
			);

			if ( $attempts >= self::MAX_ATTEMPTS ) {
				self::retire_variation( $variation_id, $e->getMessage() );
			}
		}
	}

	/**
	 * Stop selecting a variation the migration cannot process.
	 *
	 * Retiring parks the counter at MAX_ATTEMPTS so the batch query skips the row.
	 * The sentinel is deliberately NOT written: the variation was never migrated, and
	 * claiming otherwise would let `resolve_variation_gallery_image_ids()` treat an
	 * empty native gallery as an intentional "no images" choice and hide the legacy
	 * images the frontend can still serve.
	 *
	 * @param int    $variation_id Variation ID.
	 * @param string $reason       Why it was retired.
	 *
	 * @return void
	 */
	protected static function retire_variation( $variation_id, $reason ) {
		update_post_meta( $variation_id, self::ATTEMPTS_META_KEY, self::MAX_ATTEMPTS );

		self::log(
			sprintf(
				'Variation %d retired after %d attempts and will be skipped: %s. Its legacy gallery is intact and still renders.',
				$variation_id,
				self::MAX_ATTEMPTS,
				$reason
			),
			'error'
		);

		/**
		 * Fires when a variation is permanently skipped by the migration.
		 *
		 * @param int    $variation_id Variation ID.
		 * @param string $reason       Why it was retired.
		 */
		do_action( 'rtwpvg_variation_gallery_migration_failed', $variation_id, $reason );
	}

	/**
	 * Record a migration message where a site owner can find it.
	 *
	 * @param string $message Message.
	 * @param string $level   WooCommerce log level.
	 *
	 * @return void
	 */
	protected static function log( $message, $level = 'warning' ) {
		if ( function_exists( 'wc_get_logger' ) ) {
			$logger = wc_get_logger();

			if ( $logger ) {
				$logger->log( $level, $message, array( 'source' => self::LOG_SOURCE ) );

				return;
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Fallback only when the WooCommerce logger is unavailable.
			error_log( self::LOG_SOURCE . ': ' . $message );
		}
	}

	/**
	 * Memory ceiling at which the batch stops and hands the rest to its successor.
	 *
	 * A migration must never be the thing that exhausts a store's memory limit, so the
	 * batch yields well before the ceiling rather than dying at it — a fatal here costs
	 * an attempt on whichever variation happened to be mid-write.
	 *
	 * @return float Bytes. INF when PHP has no limit, so the check never trips.
	 */
	protected static function get_memory_cap() {
		$limit = trim( (string) ini_get( 'memory_limit' ) );

		// -1 (and an empty value) mean no limit; nothing to guard against.
		if ( '' === $limit || '-1' === $limit ) {
			return INF;
		}

		$bytes = wp_convert_hr_to_bytes( $limit );

		if ( $bytes <= 0 ) {
			return INF;
		}

		/**
		 * Fraction of the PHP memory limit a migration batch may occupy.
		 *
		 * @param float $ratio Between 0 and 1.
		 */
		$ratio = (float) apply_filters( 'rtwpvg_variation_gallery_migration_memory_ratio', 0.75 );
		$ratio = min( 0.9, max( 0.1, $ratio ) );

		return $bytes * $ratio;
	}

	/**
	 * Release a migrated variation from WordPress's in-memory caches.
	 *
	 * @param int $variation_id Variation ID.
	 *
	 * @return void
	 */
	protected static function free_variation_cache( $variation_id ) {
		// Its meta was just rewritten, so the cached copy is stale anyway; dropping it
		// is both the correct invalidation and what keeps the batch's memory flat.
		wp_cache_delete( $variation_id, 'post_meta' );
		wp_cache_delete( $variation_id, 'posts' );
	}

	/**
	 * Point in time after which the batch stops and hands the rest to its successor.
	 *
	 * Action Scheduler allows a queue runner 30 seconds by default and only tests that
	 * budget *between* actions — never inside one. This migration does up to
	 * BATCH_SIZE full variation saves inside a single action, so without its own budget
	 * a slow host runs past both that limit and PHP's `max_execution_time` and is killed
	 * mid-write. Finishing well inside the window keeps every batch survivable.
	 *
	 * @return float Deadline as a microtime value.
	 */
	protected static function get_deadline() {
		$queue_limit = absint( apply_filters( 'action_scheduler_queue_runner_time_limit', 30 ) );
		$budget      = (int) floor( max( 5, $queue_limit ) * 0.6 );

		$php_limit = (int) ini_get( 'max_execution_time' );

		// 0 means unlimited (CLI); only clamp when PHP imposes a real ceiling.
		if ( $php_limit > 0 ) {
			$budget = min( $budget, (int) floor( $php_limit * 0.6 ) );
		}

		/**
		 * Seconds one migration batch may spend before deferring the rest.
		 *
		 * @param int $budget Seconds.
		 */
		$budget = absint( apply_filters( 'rtwpvg_variation_gallery_migration_time_budget', $budget ) );

		return microtime( true ) + max( 1, $budget );
	}

	/**
	 * Fetch variation IDs that still carry an unmigrated legacy gallery.
	 *
	 * @param int $limit Maximum number of IDs to return.
	 *
	 * @return array
	 */
	protected static function get_pending_variation_ids( $limit ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT legacy.post_id
			FROM {$wpdb->postmeta} AS legacy
			INNER JOIN {$wpdb->posts} AS posts
				ON posts.ID = legacy.post_id
				AND posts.post_type = 'product_variation'
			LEFT JOIN {$wpdb->postmeta} AS migrated
				ON migrated.post_id = legacy.post_id
				AND migrated.meta_key = %s
			LEFT JOIN {$wpdb->postmeta} AS attempts
				ON attempts.post_id = legacy.post_id
				AND attempts.meta_key = %s
			WHERE legacy.meta_key = %s
				AND legacy.meta_value <> ''
				AND legacy.meta_value <> 'a:0:{}'
				AND migrated.post_id IS NULL
				AND ( attempts.meta_value IS NULL OR CAST( attempts.meta_value AS UNSIGNED ) < %d )
			GROUP BY legacy.post_id
			ORDER BY legacy.post_id ASC
			LIMIT %d",
			Functions::NATIVE_GALLERY_SENTINEL_META_KEY,
			self::ATTEMPTS_META_KEY,
			Functions::LEGACY_GALLERY_META_KEY,
			self::MAX_ATTEMPTS,
			absint( $limit )
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared immediately above.
		return array_map( 'absint', $wpdb->get_col( $query ) );
	}

	/**
	 * Resolve WooCommerce's Action Scheduler queue when it is available.
	 *
	 * @return \WC_Queue_Interface|null
	 */
	protected static function get_queue() {
		if ( ! function_exists( 'WC' ) || ! method_exists( WC(), 'queue' ) ) {
			return null;
		}

		return WC()->queue();
	}
}

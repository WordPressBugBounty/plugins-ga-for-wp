<?php
/**
 * Trait: GA4WP_Stats_Renderer
 *
 * Outputs KPI stat cards, percentage-change badges, and comparison helpers
 * used by both the admin dashboard and the WP dashboard widgets.
 */
if ( ! defined( 'ABSPATH' ) ) die;

trait GA4WP_Stats_Renderer {

	/**
	 * Map a sequential index to a KPI card icon color pair, cycling through a fixed palette.
	 *
	 * @param int $idx Zero-based card index.
	 * @return array{0:string,1:string} [$background_color, $accent_color] hex values.
	 */
	private function get_icon_bg( $idx ) {
		$pairs = [
			[ '#EFF6FF', '#2563EB' ], // brand blue
			[ '#ECFDF8', '#0EA5A0' ], // teal
			[ '#F5F3FF', '#7C3AED' ], // violet
			[ '#FFFBEB', '#D97706' ], // amber
			[ '#F0F9FF', '#0284C7' ], // sky
			[ '#ECFDF5', '#059669' ], // emerald
			[ '#FFF1F4', '#E11D48' ], // rose
			[ '#EEF2FF', '#4F46E5' ], // indigo
		];
		return $pairs[ $idx % count( $pairs ) ];
	}

	/**
	 * Convert a raw seconds value into a compact human-readable duration.
	 *
	 * Chooses the coarsest useful unit: seconds under a minute, minutes (with
	 * leftover seconds) under an hour, hours (with leftover minutes) under a
	 * day, otherwise days (with leftover hours).
	 *
	 * @param float|int $seconds Duration in seconds.
	 * @return string Compact duration string, e.g. "45s", "3m 12s", "2h 5m", "1d 4h".
	 */
	public function format_duration_seconds( $seconds ) {
		$seconds = (float) $seconds;
		if ( $seconds < 60 ) {
			return round( $seconds ) . 's';
		}
		if ( $seconds < HOUR_IN_SECONDS ) {
			$m = floor( $seconds / 60 );
			$s = round( $seconds - ( $m * 60 ) );
			return $s > 0 ? $m . 'm ' . $s . 's' : $m . 'm';
		}
		if ( $seconds < DAY_IN_SECONDS ) {
			$h = floor( $seconds / HOUR_IN_SECONDS );
			$m = floor( ( $seconds - ( $h * HOUR_IN_SECONDS ) ) / 60 );
			return $m > 0 ? $h . 'h ' . $m . 'm' : $h . 'h';
		}
		$d = floor( $seconds / DAY_IN_SECONDS );
		$h = floor( ( $seconds - ( $d * DAY_IN_SECONDS ) ) / HOUR_IN_SECONDS );
		return $h > 0 ? $d . 'd ' . $h . 'h' : $d . 'd';
	}

	/**
	 * Calculate the percentage change from an old value to a new value.
	 *
	 * @param float|int $stat_old Previous-period value.
	 * @param float|int $stat_new Current-period value.
	 * @return float|string The percentage change rounded to 2 decimals, or '∞' when the old
	 *                      value is 0 and the new value is greater than 0, or '--' when both are 0.
	 */
	public function stat_percentage_cal( $stat_old, $stat_new ) {
		if ( $stat_old == 0 ) {
			return $stat_new > 0 ? '∞' : '--';
		}
		return round( ( ( $stat_new - $stat_old ) / $stat_old ) * 100, 2 );
	}

	/**
	 * Determine the badge CSS class, Material icon and arrow character for a percentage change.
	 *
	 * $type controls whether an increase is "good" (shown green, e.g. for
	 * users/revenue) or "bad" (shown red, e.g. for bounce rate/errors) —
	 * when truthy an increase is favorable, when falsy a decrease is favorable.
	 *
	 * @param float|int|string $percentage Percentage change value, or '--'/0 for no change.
	 * @param mixed            $type       Truthy if an increase in this metric is desirable, falsy otherwise.
	 * @return array{style:string,color:string,badge_class:string,arrow:string} Display info: the
	 *              Material icon ligature ('style'), semantic color ('green'/'red'/'flat'), the CSS
	 *              badge class, and a Unicode arrow character.
	 */
	public function comp_icon_style_color( $percentage, $type ) {
		if ( $percentage === '--' || $percentage == 0 ) {
			return [ 'style' => 'remove', 'color' => 'flat', 'badge_class' => 'ga4wp-badge-flat', 'arrow' => '→' ];
		}
		if ( $percentage > 0 ) {
			$good = $type ? true : false;
			return [
				'style'      => 'arrow_upward',
				'color'      => $good ? 'green' : 'red',
				'badge_class' => $good ? 'ga4wp-badge-up' : 'ga4wp-badge-dn',
				'arrow'      => '↑',
			];
		}
		// < 0
		$good = $type ? false : true;
		return [
			'style'      => 'arrow_downward',
			'color'      => $good ? 'green' : 'red',
			'badge_class' => $good ? 'ga4wp-badge-up' : 'ga4wp-badge-dn',
			'arrow'      => '↓',
		];
	}

	/**
	 * Render the KPI stat card grid used on the admin dashboard.
	 *
	 * Echoes the grid's inline styles followed by one card per entry in
	 * $stats_data: an icon, a current-vs-previous change badge, the
	 * formatted value (applying duration/money/percent/suffix formatting
	 * per $stats_array), the metric label, and the previous-period value.
	 *
	 * @param array  $stats_data      Metric data keyed by stat name, each value a
	 *                                [current, previous] pair.
	 * @param array  $stats_array     Metric definitions keyed by stat name, each an array of
	 *                                [icon, label, unit/format, is_prefix, is_good_when_up].
	 * @param string $currency_symbol Currency symbol used to format 'money'-unit stats.
	 */
	public function publish_stat_data( $stats_data, $stats_array, $currency_symbol ) {
		if ( ! is_array( $stats_data ) || empty( $stats_data ) ) {
			return;
		}
		echo '<style>
		.ga4wp-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;}
		@media(max-width:1100px){.ga4wp-kpi-grid{grid-template-columns:repeat(2,1fr);}}
		@media(max-width:540px){.ga4wp-kpi-grid{grid-template-columns:1fr;}}
		.ga4wp-kpi-card{background:#fff;border:1px solid #E4E8F2;border-radius:14px;padding:16px 18px;box-shadow:0 1px 4px rgba(15,23,42,.06),0 2px 8px rgba(15,23,42,.04);transition:all .2s;position:relative;overflow:hidden;animation:ga4wpFadeUp .45s ease both;}
		.ga4wp-kpi-card::after{content:"";position:absolute;bottom:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--kc1),var(--kc2));border-radius:0 0 14px 14px;}
		.ga4wp-kpi-card:hover{box-shadow:0 4px 16px rgba(15,23,42,.09),0 8px 32px rgba(15,23,42,.06);transform:translateY(-2px);border-color:#D0D6E8;}
		.ga4wp-kpi-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;}
		.ga4wp-kpi-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
		.ga4wp-kpi-icon .material-icons{font-size:18px;}
		.ga4wp-badge-up{display:inline-flex;align-items:center;gap:2px;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:20px;background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;font-family:"IBM Plex Mono",monospace;}
		.ga4wp-badge-dn{display:inline-flex;align-items:center;gap:2px;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:20px;background:#FFF1F4;color:#E11D48;border:1px solid #FECDD3;font-family:"IBM Plex Mono",monospace;}
		.ga4wp-badge-flat{display:inline-flex;align-items:center;gap:2px;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:20px;background:#F1F5F9;color:#64748B;border:1px solid #E4E8F2;font-family:"IBM Plex Mono",monospace;}
		.ga4wp-badge-up .material-icons,.ga4wp-badge-dn .material-icons,.ga4wp-badge-flat .material-icons{font-size:10px;}
		.ga4wp-kpi-val{font-size:26px;font-weight:700;letter-spacing:-.5px;color:#0F172A;line-height:1.05;}
		.ga4wp-kpi-label{font-size:11.5px;color:#475569;margin-top:3px;}
		.ga4wp-kpi-sep{border-top:1px solid #E4E8F2;margin:10px 0 8px;}
		.ga4wp-kpi-prev{font-size:11px;color:#94A3B8;display:flex;align-items:center;gap:4px;font-family:"IBM Plex Mono",monospace;}
		.ga4wp-kpi-prev .material-icons{font-size:12px;}
		@keyframes ga4wpFadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
		</style>';

		echo '<div class="ga4wp-kpi-grid">';
		$idx = 0;
		foreach ( $stats_data as $stat_name => $stat ) {
			$stat[0]     = round( $stat[0], 4 );
			$is_duration = ! empty( $stats_array[ $stat_name ][2] ) && $stats_array[ $stat_name ][2] === 's' && empty( $stats_array[ $stat_name ][3] );
			if ( $is_duration ) {
				$stat_value = $this->format_duration_seconds( $stat[0] );
			} elseif ( ! empty( $stats_array[ $stat_name ][2] ) ) {
				if ( $stats_array[ $stat_name ][3] ) {
					$stat_value = ( $stats_array[ $stat_name ][2] == 'money' ) ? $currency_symbol . $stat[0] : $stats_array[ $stat_name ][2] . $stat[0];
				} else {
					$stat_value = ( $stats_array[ $stat_name ][2] == '100%' ) ? ( $stat[0] * 100 . '%' ) : ( $stat[0] . $stats_array[ $stat_name ][2] );
				}
			} else {
				$stat_value = $stat[0];
			}
			$comp_pct  = $this->stat_percentage_cal( $stat[1], $stat[0] );
			$icon_info = $this->comp_icon_style_color( $comp_pct, $stats_array[ $stat_name ][4] );
			$badge_pct = ( $comp_pct === '--' || $comp_pct === '∞' ) ? $comp_pct : $comp_pct . '%';
			$colors    = $this->get_icon_bg( $idx );
			$icon_name = ! empty( $stats_array[ $stat_name ][0] ) ? $stats_array[ $stat_name ][0] : 'bar_chart';
			$label     = ! empty( $stats_array[ $stat_name ][1] ) ? $stats_array[ $stat_name ][1] : $stat_name;
			$prev_val  = isset( $stat[1] ) ? round( $stat[1], 4 ) : 0;
			$prev_val  = $is_duration ? $this->format_duration_seconds( $prev_val ) : $prev_val;
			echo '<div class="ga4wp-kpi-card" style="--kc1:' . $colors[1] . ';--kc2:' . $colors[1] . 'aa;animation-delay:' . ( $idx * 0.06 ) . 's">';
			echo '  <div class="ga4wp-kpi-top">';
			echo '    <div class="ga4wp-kpi-icon" style="background:' . $colors[0] . '"><span class="material-icons" style="color:' . $colors[1] . '">' . esc_html( $icon_name ) . '</span></div>';
			echo '    <span class="' . $icon_info['badge_class'] . '"><span class="material-icons">' . $icon_info['style'] . '</span>' . $badge_pct . '</span>';
			echo '  </div>';
			echo '  <div class="ga4wp-kpi-val">' . esc_html( $stat_value ) . '</div>';
			echo '  <div class="ga4wp-kpi-label">' . esc_html( $label ) . '</div>';
			echo '  <div class="ga4wp-kpi-sep"></div>';
			echo '  <div class="ga4wp-kpi-prev"><span class="material-icons">history</span>Prev. ' . esc_html( $prev_val ) . '</div>';
			echo '</div>';
			$idx++;
		}
		echo '</div>';
	}

	/**
	 * Render compact two-column stat tiles for the WP dashboard widget.
	 *
	 * Resolves the current stat definitions (GA4 property or report-view
	 * flavor) via get_current_dash_settings() and GA4WP_Settings, then
	 * echoes one tile per entry in $stats_data with its formatted value,
	 * label, and a current-vs-previous change badge.
	 *
	 * @param array $stats_data Metric data keyed by stat name, each value a [current, previous] pair.
	 */
	public function publish_stat_data_2( $stats_data ) {
		if ( class_exists( 'WooCommerce' ) ) {
			$currency_symbol = get_woocommerce_currency_symbol();
		} else {
			$currency_symbol = '$';
		}
		$ga4wp_dash_settings = $this->get_current_dash_settings();
		// The 'dash' report/stat definitions are premium-only; the free version's
		// 'audience' definitions cover the same reports (same shape/order) under a
		// different name, so the WP dashboard widget must key off whichever exists.
		$tab_id              = (function_exists('gfw_fs') && gfw_fs()->can_use_premium_code__premium_only()) ? 'dash' : 'audience';
		$array_name          = ! empty( $ga4wp_dash_settings['report_view'] ) ? 'ga4wp_dash_stats_data_' . $tab_id : 'ga4wp_dash_stats_data_ga4_' . $tab_id;
		$stats_array         = GA4WP_Settings::get_instance()->$array_name;

		if ( ! is_array( $stats_data ) || empty( $stats_data ) ) {
			return;
		}

		echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:8px 0;">';
		$idx = 0;
		foreach ( $stats_data as $stat_name => $stat ) {
			$stat[0]     = round( $stat[0], 4 );
			$is_duration = ! empty( $stats_array[ $stat_name ][2] ) && $stats_array[ $stat_name ][2] === 's' && empty( $stats_array[ $stat_name ][3] );
			if ( $is_duration ) {
				$stat_value = $this->format_duration_seconds( $stat[0] );
			} elseif ( ! empty( $stats_array[ $stat_name ][2] ) ) {
				if ( $stats_array[ $stat_name ][3] ) {
					$stat_value = ( $stats_array[ $stat_name ][2] == 'money' ) ? $currency_symbol . $stat[0] : $stats_array[ $stat_name ][2] . $stat[0];
				} else {
					$stat_value = ( $stats_array[ $stat_name ][2] == '100%' ) ? ( $stat[0] * 100 . '%' ) : ( $stat[0] . $stats_array[ $stat_name ][2] );
				}
			} else {
				$stat_value = $stat[0];
			}
			$comp_pct  = $this->stat_percentage_cal( $stat[1], $stat[0] );
			$icon_info = $this->comp_icon_style_color( $comp_pct, $stats_array[ $stat_name ][4] );
			$badge_pct = ( $comp_pct === '--' || $comp_pct === '∞' ) ? $comp_pct : $comp_pct . '%';
			$label     = ! empty( $stats_array[ $stat_name ][1] ) ? $stats_array[ $stat_name ][1] : $stat_name;
			echo '<div style="background:#F8F9FC;border:1px solid #E4E8F2;border-radius:10px;padding:10px 12px;">';
			echo '  <div style="font-size:18px;font-weight:700;color:#0F172A;letter-spacing:-.3px;">' . esc_html( $stat_value ) . '</div>';
			echo '  <div style="font-size:11px;color:#475569;margin-top:2px;">' . esc_html( $label ) . '</div>';
			echo '  <div style="margin-top:6px;"><span class="' . $icon_info['badge_class'] . '"><span class="material-icons" style="font-size:10px;">' . $icon_info['style'] . '</span>' . $badge_pct . '</span></div>';
			echo '</div>';
			$idx++;
		}
		echo '</div>';
	}

	/**
	 * Build the inline current-vs-previous percentage-change markup for a single metric cell.
	 *
	 * @param float|int $data1 Current-period value.
	 * @param float|int $data2 Previous-period value.
	 * @return string|null HTML fragment with the percentage change and an up/down/flat badge icon,
	 *                     or null if $data2 is negative (falls through without returning).
	 */
	public function publish_compare_stats( $data1, $data2 ) {
		if ( (float) $data2 == 0 ) {
			if ( (float) $data1 > 0 ) {
				return '∞% <span class="ga4wp-badge-up"><span class="material-icons" style="font-size:10px;">arrow_upward</span></span>';
			} else {
				return '—% <span class="ga4wp-badge-flat"><span class="material-icons" style="font-size:10px;">remove</span></span>';
			}
		} elseif ( (float) $data2 > 0 ) {
			$n = round( (float) ( ( ( $data1 - $data2 ) / $data2 ) * 100 ), 2 );
			if ( $n > 0 ) {
				return $n . '% <span class="ga4wp-badge-up"><span class="material-icons" style="font-size:10px;">arrow_upward</span></span>';
			} elseif ( $n < 0 ) {
				return $n . '% <span class="ga4wp-badge-dn"><span class="material-icons" style="font-size:10px;">arrow_downward</span></span>';
			} else {
				return '0% <span class="ga4wp-badge-flat"><span class="material-icons" style="font-size:10px;">remove</span></span>';
			}
		}
	}

	/**
	 * Build a table <td> cell containing a current-vs-previous percentage-change badge.
	 *
	 * @param float|int $previous Previous-period value.
	 * @param float|int $current  Current-period value.
	 * @return string HTML `<td>...</td>` markup with an up/down/flat badge and percentage.
	 */
	public function percentage_stats_info( $previous, $current ) {
		if ( $previous > 0 ) {
			$pct = $current > 0 ? round( ( ( $current - $previous ) / $previous ) * 100, 2 ) : 0;
		} elseif ( $current > 0 ) {
			$pct = 100;
		} else {
			$pct = 0;
		}
		if ( $pct > 0 ) {
			return '<td><span class="ga4wp-badge-up"><span class="material-icons" style="font-size:10px;">arrow_upward</span>+' . round( $pct, 2 ) . '%</span></td>';
		} elseif ( $pct < 0 ) {
			return '<td><span class="ga4wp-badge-dn"><span class="material-icons" style="font-size:10px;">arrow_downward</span>' . round( $pct, 2 ) . '%</span></td>';
		} else {
			return '<td><span class="ga4wp-badge-flat"><span class="material-icons" style="font-size:10px;">remove</span>0%</span></td>';
		}
	}
}

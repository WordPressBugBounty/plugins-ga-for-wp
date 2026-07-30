<?php
/**
 * Trait: GA4WP_Chart_Renderer
 *
 * All chart.js-based output: bar, line, doughnut, funnel, and comparison table.
 * Each method echoes HTML + an inline <script> block that calls Chart().
 */
if ( ! defined( 'ABSPATH' ) ) die;

trait GA4WP_Chart_Renderer {

	/* Set true while rendering WP dashboard widgets — their export/info icons
	   depend on JS that doesn't reliably run in that context, so they're hidden. */
	private $suppress_chart_header_actions = false;

	private $chart_colors = [
		[ 'border' => '#2563EB', 'bg' => 'rgba(37,99,235,0.10)',   'prev_border' => 'rgba(37,99,235,0.40)',   'prev_bg' => 'transparent' ],
		[ 'border' => '#0EA5A0', 'bg' => 'rgba(14,165,160,0.10)',  'prev_border' => 'rgba(14,165,160,0.40)',  'prev_bg' => 'transparent' ],
		[ 'border' => '#7C3AED', 'bg' => 'rgba(124,58,237,0.10)',  'prev_border' => 'rgba(124,58,237,0.40)',  'prev_bg' => 'transparent' ],
		[ 'border' => '#D97706', 'bg' => 'rgba(217,119,6,0.10)',   'prev_border' => 'rgba(217,119,6,0.40)',   'prev_bg' => 'transparent' ],
		[ 'border' => '#0284C7', 'bg' => 'rgba(2,132,199,0.10)',   'prev_border' => 'rgba(2,132,199,0.40)',   'prev_bg' => 'transparent' ],
		[ 'border' => '#059669', 'bg' => 'rgba(5,150,105,0.10)',   'prev_border' => 'rgba(5,150,105,0.40)',   'prev_bg' => 'transparent' ],
		[ 'border' => '#E11D48', 'bg' => 'rgba(225,29,72,0.10)',   'prev_border' => 'rgba(225,29,72,0.40)',   'prev_bg' => 'transparent' ],
		[ 'border' => '#4F46E5', 'bg' => 'rgba(79,70,229,0.10)',   'prev_border' => 'rgba(79,70,229,0.40)',   'prev_bg' => 'transparent' ],
	];

	private $donut_colors = [ '#2563EB', '#0EA5A0', '#7C3AED', '#D97706', '#0284C7', '#059669', '#E11D48', '#4F46E5' ];

	/* ── Card wrapper helpers ── */

	/**
	 * Echo the opening markup of a report card: box wrapper, title with icon,
	 * and (unless header actions are suppressed) an info toggle that reveals
	 * the report description.
	 *
	 * When a description is present, also echoes an inline <script> that wires
	 * a click handler on the info icon to show/hide the description text.
	 *
	 * @param string $title       Card title displayed in the header.
	 * @param string $description Optional description text revealed via the info toggle; pass '' to disable it.
	 * @param string $icon        Material Icons ligature name shown next to the title. Default 'show_chart'.
	 */
	private function chart_card_open( $title, $description, $icon = 'show_chart' ) {
		// Each dashboard tab is fetched via its own AJAX request/PHP process, and
		// previously-loaded tabs stay in the DOM (just hidden) rather than being
		// removed — a per-request counter would restart at 1 for every tab, producing
		// duplicate ids once more than one tab's markup is present. uniqid() keeps
		// these globally unique across requests instead.
		$uid     = 'ga4wp-ci-' . str_replace( '.', '', uniqid( '', true ) );
		$desc_id = 'ga4wp-cd-' . str_replace( '.', '', uniqid( '', true ) );
		$has_tip = ! empty( trim( (string) $description ) );
		$show_actions = empty( $this->suppress_chart_header_actions );
		echo '<div class="ga4wp-box">';
		echo '<div class="ga4wp-box-header">';
		echo '  <p class="ga4wp-box-title"><span class="material-icons" style="font-size:15px;color:#2563EB;vertical-align:middle;margin-right:6px;">' . esc_html( $icon ) . '</span>' . esc_html( $title ) . '</p>';
		if ( $show_actions ) {
			echo '  <div class="ga4wp-box-header-right">';
			if ( $has_tip ) {
				echo '    <i id="' . esc_attr( $uid ) . '" class="chart-info material-icons" aria-expanded="false" aria-controls="' . esc_attr( $desc_id ) . '">info_outline</i>';
			} else {
				echo '    <i class="chart-info material-icons" style="opacity:.35;cursor:default;pointer-events:none;">info_outline</i>';
			}
			echo '  </div>';
		}
		echo '</div>';
		if ( $has_tip && $show_actions ) {
			echo '<p id="' . esc_attr( $desc_id ) . '" class="ga4wp-box-description" style="display:none;">' . esc_html( $description ) . '</p>';
			echo '<script>(function(){';
			echo   'var btn=document.getElementById("' . esc_js( $uid ) . '");';
			echo   'var box=document.getElementById("' . esc_js( $desc_id ) . '");';
			echo   'if(btn&&box){btn.addEventListener("click",function(){';
			echo     'var open=box.style.display==="block";';
			echo     'box.style.display=open?"none":"block";';
			echo     'btn.style.color=open?"":"#2563EB";';
			echo     'btn.setAttribute("aria-expanded",String(!open));';
			echo   '});}';
			echo '})()</script>';
		}
	}

	/**
	 * Echo the closing markup of a report card opened by chart_card_open().
	 */
	private function chart_card_close() {
		echo '</div>';
	}

	/**
	 * Get the shared Chart.js font/color defaults as a raw JS object literal string.
	 *
	 * @return string A JS object literal (not JSON — includes an unquoted font-family value).
	 */
	private function chart_defaults_js() {
		return '{
			font: { family: "\'Sora\', sans-serif", size: 11 },
			color: "#94A3B8"
		}';
	}

	/**
	 * Get the shared Chart.js tooltip style defaults as a raw JS object literal string.
	 *
	 * @return string A JS object literal used to configure the tooltip plugin's appearance.
	 */
	private function tooltip_defaults_js() {
		return '{
			backgroundColor: "#FFFFFF",
			borderColor: "#E4E8F2",
			borderWidth: 1,
			titleColor: "#0F172A",
			bodyColor: "#475569",
			padding: 10,
			cornerRadius: 8,
			boxPadding: 4
		}';
	}

	/* ══════════════════════════════════════════════════════════
	   TABLE
	   ══════════════════════════════════════════════════════════ */

	/**
	 * Render a report as an HTML comparison table, echoing the full table markup
	 * inside a report card.
	 *
	 * Supports single-value rows, current/previous comparison rows (with a
	 * computed % change column), multi-metric comparison rows (e.g. funnel
	 * step percentages), composite multi-dimension row keys ("dimA|||dimB"),
	 * and special-cased leading columns (country flag, source favicon, or a
	 * video-platform icon when row data includes a 'url'). Rows are sorted by
	 * their current/numeric value descending and numbered by rank. Adds a
	 * "Show more rows" button when there are more than 10 rows.
	 *
	 * @param string       $location    DOM id for the wrapping <div> that contains the table.
	 * @param string       $title       Report title, also used to look up a header icon and to
	 *                                  branch on special-case formatting (e.g. duration, flags, favicons).
	 * @param string|array $xtitle      Column header(s) for the dimension column(s).
	 * @param string|array $ytitle      Column header(s) for the metric column(s).
	 * @param array        $chart_data  Report rows keyed by dimension value, each value being either
	 *                                  a scalar, an array of stdClass objects with a ->value property,
	 *                                  or an array with 'current'/'previous' (and optionally 'url') keys.
	 * @param string       $description Description text shown via the card's info toggle.
	 */
	public function publish_simple_table( $location, $title, $xtitle, $ytitle, $chart_data, $description ) {
		$icon_map = [
			'Country Based Users Report'         => 'public',
			'Language Based Users Report'        => 'translate',
			'Users Based on Channels'            => 'alt_route',
			'Users Based on Source'              => 'travel_explore',
			'Users Based on Screen Sizes'        => 'devices',
			'Users Based on Medium'              => 'mediation',
			'Page Performance Report'            => 'description',
			'Avg. Time Spend of Page'            => 'timer',
			'Users based on Age-Group'           => 'group',
			'Gender Based User Report'           => 'wc',
			'Product Base Revenue Report'        => 'inventory',
			'Source Base Revenue Report'         => 'payments',
			'State/Region Base Revenue Report'   => 'map',
			'Device base conversion share'       => 'devices_other',
			'Ad Group Cost Report'               => 'attach_money',
			'Ad Group Success Report'            => 'trending_up',
			'Ad Search Query Reports'            => 'search',
			'Video Performance Report'           => 'play_circle',
		];
		$icon = isset( $icon_map[ $title ] ) ? $icon_map[ $title ] : 'table_chart';
		$this->chart_card_open( $title, $description, $icon );

		$has_compare       = false;
		$has_multi_compare = false;
		$has_url           = false;
		if ( is_array( $chart_data ) && ! empty( $chart_data ) ) {
			$first             = reset( $chart_data );
			$has_compare       = is_array( $first ) && array_key_exists( 'current', $first );
			$has_multi_compare = $has_compare && is_array( $first['current'] );
			$has_url           = is_array( $first ) && isset( $first['url'] );
		}


		echo '<div id="' . esc_attr( $location ) . '">';
		echo '<div class="responsive-table">';
		echo '<table class="striped highlight ga4wp-compare-table">';
		echo '<thead><tr>';
		echo '<th class="ga4wp-th-rank">#</th>';
		if ( $has_url ) echo '<th style="width:36px;padding-right:0;"></th>';
		if ( is_array( $xtitle ) ) {
			foreach ( $xtitle as $xt ) echo '<th>' . esc_html( $xt ) . '</th>';
		} else {
			echo '<th>' . esc_html( $xtitle ) . '</th>';
		}
		if ( is_array( $ytitle ) ) {
			foreach ( $ytitle as $t ) echo '<th>' . esc_html( $t ) . '</th>';
			if ( $has_compare && ! $has_multi_compare ) {
				echo '<th>' . esc_html__( 'Prev. Period', 'ga-for-wp-text' ) . '</th>';
				echo '<th>' . esc_html__( 'Change', 'ga-for-wp-text' ) . '</th>';
			}
		} else {
			echo '<th>' . esc_html( $ytitle ) . '</th>';
			if ( $has_compare && ! $has_multi_compare ) {
				echo '<th>' . esc_html__( 'Prev. Period', 'ga-for-wp-text' ) . '</th>';
				echo '<th>' . esc_html__( 'Change', 'ga-for-wp-text' ) . '</th>';
			}
		}
		echo '</tr></thead><tbody>';

		$j = 1;
		$i = 0;
		$is_duration = $title === 'Avg. Time Spend of Page' || ( is_string( $ytitle ) && stripos( $ytitle, 'sec' ) !== false );
		$fmt = $is_duration
			? fn( $v ) => $this->format_duration_seconds( $v )
			: fn( $v ) => fmod( $v, 1 ) == 0 ? number_format( (int) $v ) : number_format( $v, 2 );
		$render_metric_cell = function ( $cur, $prev ) use ( $fmt ) {
			$diff = $cur - $prev;
			$pct  = $prev > 0 ? round( ( $diff / $prev ) * 100, 1 ) : ( $cur > 0 ? 100 : 0 );
			echo '<td class="ga4wp-td-multi">';
			echo '<span class="ga4wp-td-val">' . $fmt( $cur ) . '</span>';
			echo '<span class="ga4wp-td-prev-inline">';
			echo '<span class="ga4wp-td-prev-num">prev: ' . $fmt( $prev ) . '</span>';
			if ( $pct > 0 ) {
				echo '&nbsp;<span class="ga4wp-td-mini-badge ga4wp-td-mini-up">+' . $pct . '%</span>';
			} elseif ( $pct < 0 ) {
				echo '&nbsp;<span class="ga4wp-td-mini-badge ga4wp-td-mini-dn">' . $pct . '%</span>';
			} else {
				echo '&nbsp;<span class="ga4wp-td-mini-badge ga4wp-td-mini-flat">0%</span>';
			}
			echo '</span></td>';
		};

		if ( is_array( $chart_data ) ) {
			uasort( $chart_data, function ( $a, $b ) {
				$av = isset( $a['current'] ) ? ( is_array( $a['current'] ) ? ( $a['current'][0] ?? 0 ) : $a['current'] ) : ( is_numeric( $a ) ? $a : 0 );
				$bv = isset( $b['current'] ) ? ( is_array( $b['current'] ) ? ( $b['current'][0] ?? 0 ) : $b['current'] ) : ( is_numeric( $b ) ? $b : 0 );
				return $bv <=> $av;
			} );

			foreach ( $chart_data as $x => $y ) {
				$rank_styles = [ '', 'background:#FEF9C3;color:#A16207;', 'background:#F1F5F9;color:#475569;', 'background:#FFF7ED;color:#C2410C;' ];
				$rs          = $j <= 3 ? $rank_styles[ $j ] : 'background:#F8F9FC;color:#94A3B8;';
				echo '<tr>';
				echo '<td><span style="width:22px;height:22px;border-radius:5px;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;' . $rs . '">' . $j . '</span></td>';
				if ( $has_url ) {
					$url = isset( $y['url'] ) ? (string) $y['url'] : '';
					if ( strpos( $url, 'youtube.com' ) !== false || strpos( $url, 'youtu.be' ) !== false ) {
						echo '<td style="padding-right:0;"><span class="material-icons-round" title="YouTube" style="font-size:18px;color:#FF0000;vertical-align:middle;">smart_display</span></td>';
					} elseif ( strpos( $url, 'vimeo.com' ) !== false ) {
						echo '<td style="padding-right:0;"><span class="material-icons-round" title="Vimeo" style="font-size:18px;color:#1AB7EA;vertical-align:middle;">play_circle</span></td>';
					} else {
						echo '<td style="padding-right:0;"><span class="material-icons-round" title="HTML5" style="font-size:18px;color:#64748B;vertical-align:middle;">videocam</span></td>';
					}
				}
				if ( $has_url && ! empty( $y['url'] ) ) {
					echo '<td class="ga4wp-td-name"><a href="' . esc_url( $y['url'] ) . '" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline;text-underline-offset:2px;">' . esc_html( $x ) . '</a></td>';
				} elseif ( is_array( $xtitle ) ) {
					// Composite key from a multi-dimension report ("dimA|||dimB") — one column per dimension.
					$x_parts = explode( '|||', (string) $x );
					foreach ( $xtitle as $col_idx => $col_label ) {
						echo '<td class="ga4wp-td-name">' . esc_html( $x_parts[ $col_idx ] ?? '' ) . '</td>';
					}
				} elseif ( $title === 'Country Based Users Report' ) {
					echo '<td class="ga4wp-td-name">' . $this->ga4wp_get_country_flag_html( (string) $x ) . esc_html( $x ) . '</td>';
				} elseif ( $title === 'Users Based on Source' ) {
					echo '<td class="ga4wp-td-name">' . $this->ga4wp_get_source_favicon_html( (string) $x ) . esc_html( $x ) . '</td>';
				} else {
					echo '<td class="ga4wp-td-name">' . esc_html( $x ) . '</td>';
				}

				if ( isset( $y['current'] ) && $has_multi_compare ) {
					$cur_vals  = (array) $y['current'];
					$prev_vals = (array) ( $y['previous'] ?? [] );
					$last_mi   = count( $cur_vals ) - 1;
					foreach ( $cur_vals as $mi => $cur_v ) {
						if ( $has_url && $mi === $last_mi ) {
							$pct_val  = max( 0.0, min( 100.0, (float) $cur_v ) );
							$prev_pct = max( 0.0, min( 100.0, (float) ( $prev_vals[ $mi ] ?? 0 ) ) );
							$diff     = $pct_val - $prev_pct;
							$chg      = $prev_pct > 0 ? round( ( $diff / $prev_pct ) * 100, 1 ) : ( $pct_val > 0 ? 100 : 0 );
							echo '<td class="ga4wp-td-multi">';
							echo '<div style="display:flex;align-items:center;gap:6px;">';
							echo '<div class="progress" style="flex:1;height:6px;margin:0;border-radius:3px;background:#E2E8F0;">';
							echo '<div class="determinate" style="width:' . $pct_val . '%;background:#2563EB;border-radius:3px;transition:width .4s;"></div></div>';
							echo '<span class="ga4wp-td-val" style="min-width:38px;white-space:nowrap;">' . $pct_val . '%</span></div>';
							echo '<span class="ga4wp-td-prev-inline"><span class="ga4wp-td-prev-num">prev: ' . $prev_pct . '%</span>';
							if ( $chg > 0 ) {
								echo '&nbsp;<span class="ga4wp-td-mini-badge ga4wp-td-mini-up">+' . $chg . '%</span>';
							} elseif ( $chg < 0 ) {
								echo '&nbsp;<span class="ga4wp-td-mini-badge ga4wp-td-mini-dn">' . $chg . '%</span>';
							} else {
								echo '&nbsp;<span class="ga4wp-td-mini-badge ga4wp-td-mini-flat">0%</span>';
							}
							echo '</span></td>';
						} else {
							$render_metric_cell( (float) $cur_v, (float) ( $prev_vals[ $mi ] ?? 0 ) );
						}
					}
				} elseif ( isset( $y['current'] ) ) {
					$cur  = (float) $y['current'];
					$prev = (float) ( $y['previous'] ?? 0 );
					$diff = $cur - $prev;
					$pct  = $prev > 0 ? round( ( $diff / $prev ) * 100, 2 ) : ( $cur > 0 ? 100 : 0 );
					echo '<td class="ga4wp-td-current"><span class="ga4wp-td-val">' . $fmt( $cur ) . '</span></td>';
					echo '<td class="ga4wp-td-prev"><span class="ga4wp-td-prev-val">' . $fmt( $prev ) . '</span></td>';
					if ( $pct > 0 ) {
						echo '<td><span class="ga4wp-badge-up"><span class="material-icons" style="font-size:11px;vertical-align:-1px;">arrow_upward</span>+' . $pct . '%<span class="ga4wp-badge-abs">+' . $fmt( abs( $diff ) ) . '</span></span></td>';
					} elseif ( $pct < 0 ) {
						echo '<td><span class="ga4wp-badge-dn"><span class="material-icons" style="font-size:11px;vertical-align:-1px;">arrow_downward</span>' . $pct . '%<span class="ga4wp-badge-abs">' . $fmt( $diff ) . '</span></span></td>';
					} else {
						echo '<td><span class="ga4wp-badge-flat"><span class="material-icons" style="font-size:11px;vertical-align:-1px;">remove</span>0%</span></td>';
					}
				} elseif ( is_array( $y ) ) {
					foreach ( $y as $key => $element ) {
						if ( is_object( $element ) ) {
							echo '<td>' . round( $element->value, 2 ) . '</td>';
						} elseif ( is_array( $element ) ) {
							if ( $i < 1 ) {
								foreach ( $element as $key2 => $object ) {
									echo '<td style="font-weight:600;">' . round( $y[$i][$j]->value ?? 0, 2 ) . ' <span style="color:#94A3B8;font-size:10px;">' . $this->publish_compare_stats( $y[$i][$j]->value ?? 0, $y[$i + 1][$j]->value ?? 0 ) . '</span></td>';
									$j++;
								}
								$j = 1;
							}
							$i++;
						}
					}
					echo '<td>—</td>';
				} else {
					echo '<td style="font-weight:600;">' . round( $y, 2 ) . '</td>';
					if ( $has_compare ) echo '<td>—</td><td>—</td>';
				}
				echo '</tr>';
				$j++;
			}
		}
		echo '</tbody></table></div></div>';
		if ( is_array( $chart_data ) && count( $chart_data ) > 10 ) {
			echo '<button type="button" class="ga4wp-view-more-btn" style="display:none;">Show more rows</button>';
		}
		$this->chart_card_close();
	}

	/**
	 * Build an <img> flag tag for a GA4 country name.
	 *
	 * Looks up the country name in the bundled country-to-flag-file map
	 * (loaded and cached on first call) and points the image at the
	 * plugin's bundled SVG flag assets.
	 *
	 * @param string $country_name GA4 country dimension value, e.g. "United States".
	 * @return string An <img> tag markup, or '' if the name is empty or has no matching flag.
	 */
	private function ga4wp_get_country_flag_html( $country_name ) {
		static $map = null;
		if ( $map === null ) {
			$file = __DIR__ . '/data/ga4wp-country-flags.php';
			$map  = is_file( $file ) ? (array) include $file : [];
		}
		$name = trim( $country_name );
		if ( $name === '' || ! isset( $map[ $name ] ) ) {
			return '';
		}
		$url = GA4WP_URL . 'assests/country-flags/' . $map[ $name ] . '.svg';
		return '<img src="' . esc_url( $url ) . '" alt="" width="18" height="13" style="width:18px;height:13px;object-fit:cover;border-radius:2px;vertical-align:middle;margin-right:7px;box-shadow:0 0 0 1px rgba(15,23,42,0.08);" loading="lazy">';
	}

	/* Bare source names GA4 reports without a hostname (sessionSource = "google", not "google.com"). */
	private static $ga4wp_known_source_domains = [
		'google'      => 'google.com',
		'bing'        => 'bing.com',
		'yahoo'       => 'yahoo.com',
		'duckduckgo'  => 'duckduckgo.com',
		'baidu'       => 'baidu.com',
		'yandex'      => 'yandex.com',
		'facebook'    => 'facebook.com',
		'instagram'   => 'instagram.com',
		'twitter'     => 'twitter.com',
		'x'           => 'x.com',
		'linkedin'    => 'linkedin.com',
		'pinterest'   => 'pinterest.com',
		'youtube'     => 'youtube.com',
		'reddit'      => 'reddit.com',
		'tiktok'      => 'tiktok.com',
		'snapchat'    => 'snapchat.com',
		'quora'       => 'quora.com',
		'telegram'    => 'telegram.org',
		'whatsapp'    => 'whatsapp.com',
		'discord'     => 'discord.com',
		'github'      => 'github.com',
		'msn'         => 'msn.com',
		'ecosia'      => 'ecosia.org',
	];

	/**
	 * Build an <img> favicon tag for a GA4 traffic source, fetched live via
	 * Google's favicon service (no image files bundled with the plugin).
	 *
	 * Bare source names GA4 reports without a hostname (e.g. sessionSource
	 * "google" rather than "google.com") are resolved via the
	 * self::$ga4wp_known_source_domains lookup table.
	 *
	 * @param string $source_name GA4 source dimension value, e.g. "google" or "example.com".
	 * @return string An <img> tag markup, or '' if the source isn't a recognizable domain
	 *                (e.g. "(direct)", "(not set)", "android-app://...").
	 */
	private function ga4wp_get_source_favicon_html( $source_name ) {
		$name = trim( $source_name );
		if ( $name === '' || $name[0] === '(' || strpos( $name, 'android-app://' ) === 0 ) {
			return '';
		}
		if ( strpos( $name, '.' ) !== false && ! strpos( $name, ' ' ) ) {
			$domain = $name;
		} elseif ( isset( self::$ga4wp_known_source_domains[ strtolower( $name ) ] ) ) {
			$domain = self::$ga4wp_known_source_domains[ strtolower( $name ) ];
		} else {
			return '';
		}
		$url = 'https://www.google.com/s2/favicons?sz=32&domain=' . rawurlencode( $domain );
		return '<img src="' . esc_url( $url ) . '" alt="" width="16" height="16" style="width:16px;height:16px;border-radius:3px;vertical-align:middle;margin-right:7px;" loading="lazy">';
	}

	/* ══════════════════════════════════════════════════════════
	   BAR CHART
	   ══════════════════════════════════════════════════════════ */

	/**
	 * Render a report as a Chart.js bar chart, echoing the report card, an
	 * optional legend, the canvas, and the inline script that instantiates
	 * the chart.
	 *
	 * Builds labels/datasets from $chart_data (single series, multi-series,
	 * or current/previous comparison), formats date-based x-axis labels for
	 * the "Total Users on Date"/"Overview Report" titles, and (for "Total
	 * Users on Date") trims the data/labels down to their second half.
	 *
	 * @param string       $location    DOM id used for the <canvas> element.
	 * @param string       $title       Report title, used for the header icon lookup and to
	 *                                  branch on date-label/trimming behavior.
	 * @param string|array $xtitle      Unused directly for axis labelling here (kept for a
	 *                                  consistent method signature across chart renderers).
	 * @param string|array $ytitle      Dataset label(s) shown in the legend/tooltips.
	 * @param array|false  $chart_data  Report rows keyed by x-axis label, values scalar, an array
	 *                                  of stdClass objects with a ->value property, or an array
	 *                                  with 'current'/'previous' keys.
	 * @param string       $description Description text shown via the card's info toggle.
	 */
	public function publish_simple_bar_chart( $location, $title, $xtitle, $ytitle, $chart_data, $description ) {
		if ( $chart_data == false ) $chart_data = [];
		$labels = [];
		$data   = [];
		foreach ( $chart_data as $x => $y ) {
			if ( $title == 'Total Users on Date' || $title == 'Overview Report' ) {
				$labels[] = date_format( date_create( $x ), 'd-M' );
			} else {
				$labels[] = $x;
			}
			if ( is_array( $y ) && isset( $y['current'] ) ) {
				$data[0][] = round( $y['current'], 2 );
				$data[1][] = round( $y['previous'] ?? 0, 2 );
			} elseif ( is_array( $y ) ) {
				$j = 0;
				foreach ( $y as $z => $object ) {
					$data[$j][] = round( $object->value, 2 );
					$j++;
				}
			} else {
				$data[] = round( $y, 2 );
			}
		}
		if ( $title == 'Total Users on Date' ) {
			$len    = count( $labels );
			$labels = array_slice( $labels, $len / 2 );
		}

		$icon_map = [ 'Overview Report' => 'bar_chart', 'Total Users on Date' => 'people', 'Ad Group Cost Report' => 'attach_money', 'Ad Group Success Report' => 'ads_click' ];
		$icon     = isset( $icon_map[ $title ] ) ? $icon_map[ $title ] : 'bar_chart';
		$this->chart_card_open( $title, $description, $icon );

		$has_compare = isset( $data[1] ) && is_array( $data[1] );
		if ( $has_compare ) {
			echo '<div style="display:flex;gap:14px;margin-bottom:10px;font-size:11px;color:#475569;">';
			$labels_display = is_array( $ytitle ) ? $ytitle : [ $ytitle, 'Previous period' ];
			foreach ( $labels_display as $li => $lbl ) {
				$col = $li === 0 ? '#2563EB' : '#CBD5E1';
				echo '<span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:2px;background:' . $col . ';display:inline-block;"></span>' . esc_html( $lbl ) . '</span>';
			}
			echo '</div>';
		}
		echo '<div class="ga4wp-chart-wrap"><canvas id="' . esc_attr( $location ) . '" role="img" aria-label="' . esc_attr( $title ) . '"></canvas></div>';
		$this->chart_card_close();
		?>
		<script>
			(function() {
				var ctx = document.getElementById('<?php echo esc_js( $location ); ?>');
				if (!ctx) return;
				var labelsData = <?php echo wp_json_encode( ! empty( $labels ) ? $labels : [] ); ?>;
				<?php
				$bar_pairs    = [ [ 'rgba(37,99,235,0.80)', '#2563EB' ], [ 'rgba(203,213,225,0.70)', '#CBD5E1' ], [ 'rgba(14,165,160,0.80)', '#0EA5A0' ], [ 'rgba(124,58,237,0.80)', '#7C3AED' ] ];
				$bar_datasets = [];
				if ( ! empty( $data ) ) {
					if ( count( $data ) == count( $data, COUNT_RECURSIVE ) ) {
						if ( $title == 'Total Users on Date' ) { $len2 = count( $data ); $data = array_slice( $data, $len2 / 2 ); }
						$bar_datasets[] = [
							'label'           => is_array( $ytitle ) ? $ytitle[0] : $ytitle,
							'data'            => array_values( $data ),
							'backgroundColor' => 'rgba(37,99,235,0.75)',
							'borderColor'     => '#2563EB',
							'borderRadius'    => 4,
							'borderSkipped'   => false,
						];
					} else {
						$j = 0;
						foreach ( $data as $arr ) {
							if ( $title == 'Total Users on Date' ) { $len2 = count( $arr ); $arr = array_slice( $arr, $len2 / 2 ); }
							$bar_datasets[] = [
								'label'           => is_array( $ytitle ) ? ( $ytitle[ $j ] ?? $ytitle[0] ) : $ytitle,
								'data'            => array_values( $arr ),
								'backgroundColor' => $bar_pairs[ $j % count( $bar_pairs ) ][0],
								'borderColor'     => $bar_pairs[ $j % count( $bar_pairs ) ][1],
								'borderRadius'    => 4,
								'borderSkipped'   => false,
							];
							$j++;
						}
					}
				} else {
					$bar_datasets[] = [
						'label'           => is_array( $ytitle ) ? $ytitle[0] : $ytitle,
						'data'            => [],
						'backgroundColor' => 'rgba(37,99,235,0.75)',
					];
				}
				echo 'var datasets = ' . wp_json_encode( $bar_datasets ) . ';';
				?>
				new Chart(ctx, {
					type: 'bar',
					data: { labels: labelsData, datasets: datasets },
					options: {
						responsive: true, maintainAspectRatio: false,
						interaction: { mode: 'index', intersect: false },
						plugins: {
							legend: { display: false },
							tooltip: { backgroundColor:'#FFF', borderColor:'#E4E8F2', borderWidth:1, titleColor:'#0F172A', bodyColor:'#475569', padding:10, cornerRadius:8 }
						},
						scales: {
							x: { grid: { display:false }, ticks: { color:'#94A3B8', font:{ size:11 }, maxTicksLimit:10 } },
							y: { beginAtZero:true, grid: { color:'rgba(15,23,42,0.05)' }, ticks: { color:'#94A3B8', font:{ size:11 } } }
						},
						animation: { duration:900, easing:'easeInOutQuart' }
					}
				});
			})();
		</script>
		<?php
	}

	/* ══════════════════════════════════════════════════════════
	   LINE CHART
	   ══════════════════════════════════════════════════════════ */

	/**
	 * Render a report as a Chart.js line chart, echoing the report card, an
	 * optional legend (with a dashed swatch for "previous"-style series), the
	 * canvas, and the inline script that instantiates the chart.
	 *
	 * Builds labels/datasets from $chart_data (single series or multiple
	 * series), formats date-based x-axis labels for the "Total Users on
	 * Date"/"Overview Report" titles, and (for "Total Users on Date") trims
	 * the data/labels down to their second half.
	 *
	 * @param string       $location    DOM id used for the <canvas> element.
	 * @param string       $title       Report title, used for the header icon lookup and to
	 *                                  branch on date-label/trimming behavior.
	 * @param string|array $xtitle      Unused directly for axis labelling here (kept for a
	 *                                  consistent method signature across chart renderers).
	 * @param string|array $ytitle      Dataset label(s) shown in the legend/tooltips.
	 * @param array|false  $chart_data  Report rows keyed by x-axis label, values scalar or an
	 *                                  array of stdClass objects with a ->value property.
	 * @param string       $description Description text shown via the card's info toggle.
	 */
	public function publish_simple_line_chart( $location, $title, $xtitle, $ytitle, $chart_data, $description ) {
		if ( $chart_data == false ) $chart_data = [];
		$labels = [];
		$data   = [];
		foreach ( $chart_data as $x => $y ) {
			if ( $title == 'Total Users on Date' || $title == 'Overview Report' ) {
				$labels[] = date_format( date_create( $x ), 'd-M' );
			} else {
				$labels[] = $x;
			}
			if ( is_array( $y ) ) {
				$j = 0;
				foreach ( $y as $z => $object ) { $data[$j][] = round( $object->value, 2 ); $j++; }
			} else {
				$data[] = round( $y, 2 );
			}
		}
		if ( $title == 'Total Users on Date' ) { $len = count( $labels ); $labels = array_slice( $labels, $len / 2 ); }

		$icon_map = [ 'Overview Report' => 'show_chart', 'Total Users on Date' => 'people' ];
		$icon     = isset( $icon_map[ $title ] ) ? $icon_map[ $title ] : 'show_chart';
		$this->chart_card_open( $title, $description, $icon );

		if ( is_array( $ytitle ) ) {
			$line_colors = [ '#2563EB', '#0EA5A0', '#7C3AED', '#D97706' ];
			echo '<div style="display:flex;gap:14px;margin-bottom:10px;flex-wrap:wrap;font-size:11px;color:#475569;">';
			foreach ( $ytitle as $li => $lbl ) {
				$col     = $line_colors[ $li % count( $line_colors ) ];
				$is_prev = stripos( $lbl, 'prev' ) !== false || stripos( $lbl, 'previous' ) !== false;
				$swatch  = $is_prev ? 'repeating-linear-gradient(90deg,' . $col . ' 0 4px,transparent 4px 7px)' : $col;
				echo '<span style="display:flex;align-items:center;gap:5px;"><span style="width:16px;height:2.5px;background:' . $swatch . ';display:inline-block;border-radius:2px;"></span>' . esc_html( $lbl ) . '</span>';
			}
			echo '</div>';
		}
		echo '<div class="ga4wp-chart-wrap"><canvas id="' . esc_attr( $location ) . '" role="img" aria-label="' . esc_attr( $title ) . '"></canvas></div>';
		$this->chart_card_close();
		?>
		<script>
			(function() {
				var ctx = document.getElementById('<?php echo esc_js( $location ); ?>');
				if (!ctx) return;
				var labelsData = <?php echo wp_json_encode( ! empty( $labels ) ? $labels : [] ); ?>;
				<?php
				$line_colors  = [ '#2563EB', '#0EA5A0', '#7C3AED', '#D97706', '#0284C7', '#059669' ];
				$fill_colors  = [ 'rgba(37,99,235,0.08)', 'rgba(14,165,160,0.08)', 'rgba(124,58,237,0.08)', 'rgba(217,119,6,0.08)' ];
				$line_datasets = [];
				if ( ! empty( $data ) ) {
					if ( count( $data ) == count( $data, COUNT_RECURSIVE ) ) {
						if ( $title == 'Total Users on Date' ) { $len2 = count( $data ); $data = array_slice( $data, $len2 / 2 ); }
						$line_datasets[] = [
							'label'            => is_array( $ytitle ) ? $ytitle[0] : $ytitle,
							'data'             => array_values( $data ),
							'borderColor'      => $line_colors[0],
							'backgroundColor'  => $fill_colors[0],
							'borderWidth'      => 2.5,
							'pointRadius'      => 0,
							'pointHoverRadius' => 5,
							'fill'             => true,
							'tension'          => 0.35,
						];
					} else {
						$j = 0;
						foreach ( $data as $arr ) {
							if ( $title == 'Total Users on Date' ) { $len2 = count( $arr ); $arr = array_slice( $arr, $len2 / 2 ); }
							$lbl = is_array( $ytitle ) ? ( $ytitle[ $j ] ?? '' ) : $ytitle;
							$line_datasets[] = [
								'label'            => $lbl,
								'data'             => array_values( $arr ),
								'borderColor'      => $line_colors[ $j % 6 ],
								'backgroundColor'  => $j === 0 ? $fill_colors[0] : 'transparent',
								'borderWidth'      => $j === 0 ? 2.5 : 1.8,
								'borderDash'       => $j === 0 ? [] : [ 5, 4 ],
								'pointRadius'      => 0,
								'pointHoverRadius' => 5,
								'fill'             => $j === 0,
								'tension'          => 0.35,
							];
							$j++;
						}
					}
				} else {
					$line_datasets[] = [
						'label'           => is_array( $ytitle ) ? $ytitle[0] : $ytitle,
						'data'            => [],
						'borderColor'     => $line_colors[0],
						'backgroundColor' => $fill_colors[0],
						'borderWidth'     => 2.5,
						'fill'            => true,
						'tension'         => 0.35,
					];
				}
				echo 'var datasets = ' . wp_json_encode( $line_datasets ) . ';';
				?>
				new Chart(ctx, {
					type: 'line',
					data: { labels: labelsData, datasets: datasets },
					options: {
						responsive: true, maintainAspectRatio: false,
						interaction: { mode: 'index', intersect: false },
						plugins: {
							legend: { display: false },
							tooltip: { backgroundColor:'#FFF', borderColor:'#E4E8F2', borderWidth:1, titleColor:'#0F172A', bodyColor:'#475569', padding:10, cornerRadius:8 }
						},
						scales: {
							x: { grid: { display:false }, ticks: { color:'#94A3B8', font:{ size:11 }, maxTicksLimit:10 } },
							y: { beginAtZero:true, grid:{ color:'rgba(15,23,42,0.05)' }, border:{ dash:[4,4], color:'transparent' }, ticks:{ color:'#94A3B8', font:{ size:11 } } }
						},
						animation: { duration:900, easing:'easeInOutQuart' }
					}
				});
			})();
		</script>
		<?php
	}

	/* ══════════════════════════════════════════════════════════
	   DOUGHNUT CHART
	   ══════════════════════════════════════════════════════════ */

	/**
	 * Render a report as a Chart.js doughnut chart, echoing the report card,
	 * an optional set of current/previous comparison "chip" segments, a
	 * color-key legend, the canvas, and the inline script that instantiates
	 * the chart.
	 *
	 * When row values include 'current'/'previous' keys, also renders a row
	 * of comparison chips above the chart showing each segment's current
	 * value, previous value, and percentage change (via stat_percentage_cal()).
	 *
	 * @param string      $location    DOM id used for the <canvas> element.
	 * @param string      $title       Report title, used for the header icon lookup.
	 * @param array|false $chart_data  Report rows keyed by segment label, values scalar, an
	 *                                 array of stdClass objects with a ->value property, or an
	 *                                 array with 'current'/'previous' keys.
	 * @param string      $description Description text shown via the card's info toggle.
	 */
	public function publish_simple_doughnut_chart( $location, $title, $chart_data, $description ) {
		if ( $chart_data == false ) $chart_data = [];

		$has_compare  = false;
		$compare_segs = [];
		if ( ! empty( $chart_data ) ) {
			$first       = reset( $chart_data );
			$has_compare = is_array( $first ) && ( array_key_exists( 'current', $first ) || array_key_exists( 'previous', $first ) );
		}

		$labels = [];
		$data   = [];
		foreach ( $chart_data as $x => $y ) {
			if ( isset( $y['current'] ) || isset( $y['previous'] ) ) {
				$labels[] = $x;
				$cur      = isset( $y['current'] )  ? (float) $y['current']  : 0;
				$prev     = isset( $y['previous'] ) ? (float) $y['previous'] : 0;
				$data[]   = round( $cur, 2 );
				$compare_segs[ $x ] = [ 'current' => $cur, 'previous' => $prev ];
			} else {
				$labels[] = $x;
				if ( is_array( $y ) ) {
					$j = 0;
					foreach ( $y as $z => $object ) { $data[$j][] = round( $object->value, 2 ); $j++; }
				} else {
					$data[] = round( $y, 2 );
				}
			}
		}

		$icon_map = [ 'Device Based Users Report' => 'devices', 'Device based conversion share' => 'devices_other' ];
		$icon     = isset( $icon_map[ $title ] ) ? $icon_map[ $title ] : 'pie_chart';
		$this->chart_card_open( $title, $description, $icon );

		if ( $has_compare && ! empty( $compare_segs ) ) {
			static $donut_seg_css = false;
			if ( ! $donut_seg_css ) {
				$donut_seg_css = true;
				echo '<style>
				.ga4wp-seg-row{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;}
				.ga4wp-seg-chip{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #E4E8F2;border-radius:10px;padding:8px 12px;position:relative;overflow:hidden;min-width:0;}
				.ga4wp-seg-chip::before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--seg-acc);border-radius:10px 0 0 10px;}
				.ga4wp-seg-num{font-size:17px;font-weight:700;color:#0F172A;letter-spacing:-.3px;line-height:1;white-space:nowrap;}
				.ga4wp-seg-meta{display:flex;flex-direction:column;gap:2px;min-width:0;}
				.ga4wp-seg-name{font-size:10.5px;font-weight:600;color:#475569;text-transform:capitalize;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
				.ga4wp-seg-prev{font-size:10px;color:#94A3B8;font-family:"IBM Plex Mono",monospace;white-space:nowrap;}
				.ga4wp-seg-badge{display:inline-flex;align-items:center;gap:1px;font-size:10px;font-weight:700;padding:2px 6px;border-radius:20px;font-family:"IBM Plex Mono",monospace;white-space:nowrap;margin-left:auto;}
				.ga4wp-seg-badge-up{background:#ECFDF5;color:#059669;border:1px solid #A7F3D0;}
				.ga4wp-seg-badge-dn{background:#FFF1F4;color:#E11D48;border:1px solid #FECDD3;}
				.ga4wp-seg-badge-flat{background:#F1F5F9;color:#64748B;border:1px solid #E4E8F2;}
				.ga4wp-seg-badge .material-icons-round{font-size:10px;}
				</style>';
			}
			$seg_palette_acc = [ '#2563EB', '#0EA5A0', '#7C3AED', '#D97706', '#059669', '#0284C7', '#E11D48', '#4F46E5' ];
			echo '<div class="ga4wp-seg-row">';
			$ci = 0;
			foreach ( $compare_segs as $seg_name => $seg ) {
				$cur      = round( $seg['current'],  2 );
				$prev     = round( $seg['previous'], 2 );
				$comp_pct = $this->stat_percentage_cal( $prev, $cur );
				$col_acc  = $seg_palette_acc[ $ci % count( $seg_palette_acc ) ];
				if ( $comp_pct === '--' || $comp_pct === '∞' ) {
					$badge_cls = 'ga4wp-seg-badge ga4wp-seg-badge-flat'; $badge_icon = 'remove'; $badge_txt = $comp_pct;
				} elseif ( $comp_pct > 0 ) {
					$badge_cls = 'ga4wp-seg-badge ga4wp-seg-badge-up';   $badge_icon = 'arrow_upward';   $badge_txt = '+' . $comp_pct . '%';
				} elseif ( $comp_pct < 0 ) {
					$badge_cls = 'ga4wp-seg-badge ga4wp-seg-badge-dn';   $badge_icon = 'arrow_downward'; $badge_txt = $comp_pct . '%';
				} else {
					$badge_cls = 'ga4wp-seg-badge ga4wp-seg-badge-flat'; $badge_icon = 'remove'; $badge_txt = '0%';
				}
				echo '<div class="ga4wp-seg-chip" style="--seg-acc:' . $col_acc . '">';
				echo '  <div class="ga4wp-seg-num">' . number_format( $cur, 0 ) . '</div>';
				echo '  <div class="ga4wp-seg-meta"><span class="ga4wp-seg-name">' . esc_html( $seg_name ) . '</span><span class="ga4wp-seg-prev">prev ' . number_format( $prev, 0 ) . '</span></div>';
				echo '  <span class="' . $badge_cls . '"><span class="material-icons-round">' . $badge_icon . '</span>' . esc_html( $badge_txt ) . '</span>';
				echo '</div>';
				$ci++;
			}
			echo '</div>';
		}

		$palette   = [ '#E11D48', '#0284C7', '#7C3AED', '#D97706', '#059669', '#2563EB', '#0EA5A0', '#4F46E5' ];
		$flat_data = ( count( $data ) == count( $data, COUNT_RECURSIVE ) ) ? $data : ( isset( $data[0] ) ? $data[0] : [] );
		echo '<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:10px;font-size:11px;color:#475569;">';
		if ( is_array( $flat_data ) ) {
			foreach ( $labels as $li => $lbl ) {
				$col = $palette[ $li % count( $palette ) ];
				$val = isset( $flat_data[ $li ] ) ? $flat_data[ $li ] : '';
				echo '<span style="display:flex;align-items:center;gap:5px;"><span style="width:10px;height:10px;border-radius:50%;background:' . $col . ';display:inline-block;"></span>' . esc_html( $lbl ) . ( $val !== '' ? ' — ' . $val : '' ) . '</span>';
			}
		}
		echo '</div>';
		echo '<div class="ga4wp-chart-wrap ga4wp-donut-wrap"><canvas id="' . esc_attr( $location ) . '" role="img" aria-label="' . esc_attr( $title ) . '"></canvas></div>';
		$this->chart_card_close();
		?>
		<script>
			(function() {
				var ctx = document.getElementById('<?php echo esc_js( $location ); ?>');
				if (!ctx) return;
				var palette   = ['#E11D48','#0284C7','#7C3AED','#D97706','#059669','#2563EB','#0EA5A0','#4F46E5'];
				var labelsData = <?php echo wp_json_encode( ! empty( $labels ) ? $labels : [] ); ?>;
				<?php
				$flat = ( count( $data ) == count( $data, COUNT_RECURSIVE ) ) ? $data : ( isset( $data[0] ) ? $data[0] : [] );
				echo 'var chartData = ' . wp_json_encode( $flat ) . ';';
				?>
				new Chart(ctx, {
					type: 'doughnut',
					data: {
						labels: labelsData,
						datasets: [{ data: chartData, backgroundColor: palette.slice(0, labelsData.length), borderColor:'#FFFFFF', borderWidth:3, hoverOffset:8 }]
					},
					options: {
						responsive:true, maintainAspectRatio:false, cutout:'66%',
						plugins: {
							legend: { display:false },
							tooltip: { backgroundColor:'#FFF', borderColor:'#E4E8F2', borderWidth:1, titleColor:'#0F172A', bodyColor:'#475569', padding:10, cornerRadius:8,
								callbacks: { label: function(c){ return ' '+c.label+': '+c.parsed; } }
							}
						},
						animation: { duration:1200, animateRotate:true }
					}
				});
			})();
		</script>
		<?php
	}

	/* ══════════════════════════════════════════════════════════
	   FUNNEL CHART
	   ══════════════════════════════════════════════════════════ */

	/**
	 * Render a purchase-journey funnel report: step summary cards, a Chart.js
	 * bar chart of the funnel steps, and a breakdown table.
	 *
	 * Aggregates $chart_data into four funnel step totals (views, added to
	 * cart, reached checkout, purchased, or the labels supplied via $ytitle),
	 * echoes a row of step cards showing each step's value, percentage of the
	 * first step, and drop-off from the previous step, then a bar chart of
	 * the same data, followed by a call to publish_simple_table() to render
	 * the device-category breakdown of the same $chart_data.
	 *
	 * @param string       $location    DOM id used for the <canvas> element and the table's DOM id (with a '-table' suffix).
	 * @param string       $title       Report title used for the chart's dataset label.
	 * @param string|array $xtitle      Passed through to the underlying breakdown table.
	 * @param string|array $ytitle      Optional funnel step labels; also passed through to the underlying breakdown table.
	 * @param array|false  $chart_data  Report rows, each either an array with a 'current' key holding
	 *                                  4 numeric step values, or an array whose [0] element holds an
	 *                                  array of stdClass objects with a ->value property.
	 * @param string       $description Description text shown via the card's info toggle.
	 */
	public function publish_simple_funnel_chart( $location, $title, $xtitle, $ytitle, $chart_data, $description ) {
		if ( $chart_data == false ) $chart_data = [];
		$data = [ 0, 0, 0, 0 ];
		foreach ( $chart_data as $seg ) {
			if ( isset( $seg['current'] ) && is_array( $seg['current'] ) ) {
				foreach ( $seg['current'] as $i => $val ) {
					if ( array_key_exists( $i, $data ) ) $data[$i] += (float) $val;
				}
			} elseif ( isset( $seg[0] ) && is_array( $seg[0] ) ) {
				foreach ( $seg[0] as $i => $object ) {
					if ( array_key_exists( $i, $data ) ) $data[$i] += (float) ( $object->value ?? 0 );
				}
			}
		}

		$labels = ( is_array( $ytitle ) && ! empty( $ytitle ) )
			? $ytitle
			: [ 'Products Views', 'Products Added to Cart', 'Products Reached Checkout', 'Products Purchased' ];

		$funnel_colors = [
			[ 'bg' => 'rgba(37,99,235,0.82)',  'border' => '#2563EB' ],
			[ 'bg' => 'rgba(14,165,160,0.82)', 'border' => '#0EA5A0' ],
			[ 'bg' => 'rgba(124,58,237,0.82)', 'border' => '#7C3AED' ],
			[ 'bg' => 'rgba(5,150,105,0.82)',  'border' => '#059669' ],
		];
		$short_labels = [
			'Products Views'            => 'Views',
			'Products Added to Cart'    => 'Add to Cart',
			'Products Reached Checkout' => 'Checkout',
			'Products Purchased'        => 'Purchased',
		];

		$this->chart_card_open( $title, $description, 'funnel' );

		echo '<div class="ga4wp-funnel-steps">';
		foreach ( $labels as $i => $label ) {
			$val  = $data[$i] ?? 0;
			$pct  = ( $i === 0 || empty( $data[0] ) ) ? 100 : round( ( $val / $data[0] ) * 100, 1 );
			$drop = ( $i > 0 && ! empty( $data[$i - 1] ) ) ? round( ( 1 - $val / $data[$i - 1] ) * 100, 1 ) : null;
			$col  = $funnel_colors[ $i % 4 ]['border'];
			echo '<div class="ga4wp-funnel-step">';
			echo '<div class="ga4wp-funnel-step-val" style="color:' . $col . ';">' . number_format( (int) $val ) . '</div>';
			echo '<div class="ga4wp-funnel-step-label">' . esc_html( $short_labels[ $label ] ?? $label ) . '</div>';
			echo '<div class="ga4wp-funnel-step-pct" style="color:' . $col . ';">' . $pct . '%</div>';
			if ( $drop !== null ) {
				echo '<div class="ga4wp-funnel-drop">&darr; ' . $drop . '% drop-off</div>';
			}
			echo '</div>';
			if ( $i < count( $labels ) - 1 ) {
				echo '<span class="ga4wp-funnel-arrow material-icons-round">chevron_right</span>';
			}
		}
		echo '</div>';

		echo '<div class="ga4wp-chart-wrap"><canvas id="' . esc_attr( $location ) . '" role="img" aria-label="' . esc_attr( $title ) . '"></canvas></div>';
		$this->chart_card_close();

		$this->publish_simple_table( $location . '-table', 'Device Category Based Purchase Journey Report', $xtitle, $ytitle, $chart_data, $description );

		$labels_js = wp_json_encode( array_values( array_map( fn( $l ) => $short_labels[ $l ] ?? $l, $labels ) ) );
		$data_js   = wp_json_encode( array_values( $data ) );
		$bg_js     = wp_json_encode( array_column( $funnel_colors, 'bg' ) );
		$brd_js    = wp_json_encode( array_column( $funnel_colors, 'border' ) );
		?>
		<script>
			(function() {
				var ctx = document.getElementById('<?php echo esc_js( $location ); ?>');
				if (!ctx) return;
				new Chart(ctx, {
					type: 'bar',
					data: {
						labels: <?php echo $labels_js; ?>,
						datasets: [{
							label: '<?php echo esc_js( $title ); ?>',
							data: <?php echo $data_js; ?>,
							backgroundColor: <?php echo $bg_js; ?>,
							borderColor: <?php echo $brd_js; ?>,
							borderWidth: 1.5, borderRadius: 8, borderSkipped: false,
						}]
					},
					options: {
						responsive:true, maintainAspectRatio:false,
						plugins: {
							legend: { display:false },
							tooltip: { backgroundColor:'#FFFFFF', borderColor:'#E4E8F2', borderWidth:1, titleColor:'#0F172A', bodyColor:'#475569', padding:10, cornerRadius:8, boxPadding:4,
								callbacks: { label: function(c){ return ' '+Number(c.parsed.y).toLocaleString(); } }
							}
						},
						scales: {
							x: { grid:{ display:false }, ticks:{ color:'#94A3B8', font:{ size:11, family:"'Sora', sans-serif" }, maxRotation:0, autoSkip:false } },
							y: { beginAtZero:true, grid:{ color:'rgba(15,23,42,0.05)' }, ticks:{ color:'#94A3B8', font:{ size:11, family:"'Sora', sans-serif" } } }
						},
						animation: { duration:900, easing:'easeInOutQuart' }
					}
				});
			})();
		</script>
		<?php
	}
}

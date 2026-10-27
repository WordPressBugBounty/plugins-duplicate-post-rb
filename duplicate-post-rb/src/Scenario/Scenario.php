<?php
/*
*      RB Duplicate Post
*      Version: 2.3.0
*      By RbPlugin
*
*      Contact: https://rbplugins.com
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\Scenario;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contexts\TransformerContext;

/**
 * Scenarios: the Assistant of a profile. A scenario is a list of steps run on
 * every copy made with the profile — replace a text, change links, set the
 * status; with PRO also patterns, custom fields and terms — and questions
 * ("variables") the copy dialog asks, e.g. {city}, used in the steps.
 *
 * Stored in the profile option "scenario":
 *   { enabled, template, vars: [{name, label, default}], steps: [{type, ...}] }
 *
 * Steps run in two phases: "data" on the new post's fields before it is
 * created, "after" on the created copy (custom fields, terms). Add-ons (the PRO
 * key) add step types with the filter rb_duplicate_post_scenario_steps.
 */
class Scenario {

    public const MAX_STEPS = 30;
    public const MAX_VARS  = 10;

    /** the answers of the copy dialog for this request: {name: value} */
    private static $answers = array();

    /** set by the copy route from the dialog's answers */
    public static function set_answers( array $answers ): void {
        $clean = array();
        foreach ( $answers as $name => $value ) {
            $name = sanitize_key( (string) $name );
            if ( $name !== '' && is_scalar( $value ) ) {
                $clean[ $name ] = sanitize_text_field( (string) $value );
            }
        }
        self::$answers = $clean;
    }

    public static function clear_answers(): void {
        self::$answers = array();
    }

    public static function init(): void {
        add_filter( 'rb_duplicate_post_new_post_data', array( self::class, 'apply_data' ), 5, 2 );
    }

    /**
     * Step types: type => [phase, pro, label, run(callable), describe(callable)?].
     * run( array $step, array $subject, array $env ): for "data" the post fields, returns them changed;
     * for "after" the subject is ['post_id' => int], returns nothing.
     */
    public static function step_types(): array {
        $types = array(
            'replace_text'  => array( 'phase' => 'data', 'pro' => false, 'run' => array( self::class, 'run_replace_text' ) ),
            'replace_links' => array( 'phase' => 'data', 'pro' => false, 'run' => array( self::class, 'run_replace_links' ) ),
            'set_status'    => array( 'phase' => 'data', 'pro' => false, 'run' => array( self::class, 'run_set_status' ) ),
        );
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        return (array) apply_filters( 'rb_duplicate_post_scenario_steps', $types );
    }

    /** regular expressions in "replace text" (PRO: the key turns this on) */
    public static function regex_allowed(): bool {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        return (bool) apply_filters( 'rb_duplicate_post_scenario_regex', false );
    }

    /* ------------------------------------------------------------------ */
    /* storing                                                             */
    /* ------------------------------------------------------------------ */

    public static function empty_scenario(): array {
        return array( 'enabled' => false, 'template' => '', 'vars' => array(), 'steps' => array() );
    }

    /**
     * Cleans a scenario as the settings screen sends it (array or JSON).
     * Unknown step types are kept (an add-on may come back), unknown keys dropped.
     */
    public static function sanitize( $value ): array {
        if ( is_string( $value ) ) {
            $value = json_decode( $value, true );
        }
        if ( ! is_array( $value ) ) {
            return self::empty_scenario();
        }
        $out = array(
            'enabled'  => ! empty( $value['enabled'] ),
            'template' => isset( $value['template'] ) ? sanitize_key( (string) $value['template'] ) : '',
            'vars'     => array(),
            'steps'    => array(),
        );
        foreach ( array_slice( (array) ( $value['vars'] ?? array() ), 0, self::MAX_VARS ) as $var ) {
            $name = isset( $var['name'] ) ? sanitize_key( (string) $var['name'] ) : '';
            if ( $name === '' || in_array( $name, array( 'title', 'date', 'site', 'id' ), true ) ) {
                continue;
            }
            $out['vars'][] = array(
                'name'    => $name,
                'label'   => isset( $var['label'] ) ? sanitize_text_field( (string) $var['label'] ) : $name,
                'default' => isset( $var['default'] ) ? sanitize_text_field( (string) $var['default'] ) : '',
            );
        }
        foreach ( array_slice( (array) ( $value['steps'] ?? array() ), 0, self::MAX_STEPS ) as $step ) {
            if ( ! is_array( $step ) || empty( $step['type'] ) ) {
                continue;
            }
            $clean = array( 'type' => sanitize_key( (string) $step['type'] ), 'on' => ! isset( $step['on'] ) || ! empty( $step['on'] ) );
            foreach ( $step as $key => $v ) {
                if ( in_array( $key, array( 'type', 'on' ), true ) ) {
                    continue;
                }
                $key = sanitize_key( (string) $key );
                if ( is_array( $v ) ) {
                    $clean[ $key ] = array_values( array_map( 'sanitize_key', array_filter( $v, 'is_scalar' ) ) );
                } elseif ( is_bool( $v ) ) {
                    $clean[ $key ] = $v;
                } elseif ( is_scalar( $v ) ) {
                    // find/replace texts keep their markup, spaces and backslashes (patterns): they act on post content
                    if ( in_array( $key, array( 'find', 'replace', 'value' ), true ) ) {
                        $clean[ $key ] = current_user_can( 'unfiltered_html' ) ? (string) $v : wp_kses_post( (string) $v );
                    } else {
                        $clean[ $key ] = sanitize_text_field( (string) $v );
                    }
                }
            }
            $out['steps'][] = $clean;
        }
        return $out;
    }

    /**
     * Steps that name a custom field (add-ons declare which field holds its key:
     * step type 'custom_field') may name a protected one - "_..." keys: page builders' data,
     * the template, the featured image - only when saved by a user who may write any
     * markup. WordPress's own Custom Fields box lets nobody change those keys, and a
     * scenario's value reaches them without any filtering. A step stored before exactly
     * as it is now (an administrator's, kept when an editor saves the profile) may stay.
     *
     * @param array      $scenario as sanitize() returns it
     * @param array|null $previous the scenario stored so far
     * @return \WP_Error|null
     */
    public static function check_permissions( array $scenario, $previous = null ) {
        if ( current_user_can( 'unfiltered_html' ) ) {
            return null;
        }
        $types = self::step_types();
        $kept  = array_map( array( self::class, 'comparable' ), is_array( $previous ) ? (array) ( $previous['steps'] ?? array() ) : array() );
        foreach ( $scenario['steps'] as $step ) {
            $field = (string) ( $types[ $step['type'] ]['custom_field'] ?? '' );
            $key   = $field !== '' ? trim( (string) ( $step[ $field ] ?? '' ) ) : '';
            if ( $key === '' || ! is_protected_meta( $key, 'post' ) || in_array( self::comparable( $step ), $kept, true ) ) {
                continue;
            }
            return new \WP_Error(
                'rest_scenario_protected_meta',
                /* translators: %s: custom field name */
                sprintf( __( 'Custom field "%s" is protected: only an administrator can use it in a scenario.', 'duplicate-post-rb' ), $key ),
                array( 'status' => 403 )
            );
        }
        return null;
    }

    /** a step as stored, apart from its on/off switch and the order of its keys */
    private static function comparable( $step ): array {
        $step = (array) $step;
        unset( $step['on'] );
        ksort( $step );
        return $step;
    }

    /** the scenario of a copy's options, or null when there is none to run */
    public static function of_options( array $options ): ?array {
        $value = $options['scenario']['value'] ?? null;
        if ( ! is_array( $value ) || empty( $value['enabled'] ) || empty( $value['steps'] ) ) {
            return null;
        }
        return $value;
    }

    /* ------------------------------------------------------------------ */
    /* running                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Tokens of a step's texts: {title}, {date}, {site}, {id} and the scenario's variables.
     */
    public static function env( array $scenario, array $source_post, ?array $answers = null ): array {
        $answers = $answers === null ? self::$answers : $answers;
        $tokens  = array(
            'title' => (string) ( $source_post['post_title'] ?? '' ),
            'date'  => date_i18n( get_option( 'date_format' ) ),
            'site'  => get_bloginfo( 'name' ),
            'id'    => (string) ( $source_post['ID'] ?? '' ),
        );
        foreach ( (array) $scenario['vars'] as $var ) {
            $tokens[ $var['name'] ] = array_key_exists( $var['name'], $answers ) && $answers[ $var['name'] ] !== '' ? $answers[ $var['name'] ] : (string) $var['default'];
        }
        return array( 'tokens' => $tokens );
    }

    public static function fill( string $text, array $env ): string {
        return preg_replace_callback( '/\{([a-z0-9_]+)\}/', function ( $m ) use ( $env ) {
            return array_key_exists( $m[1], $env['tokens'] ) ? $env['tokens'][ $m[1] ] : $m[0];
        }, $text );
    }

    /** the "data" phase: on the fields of the post about to be created */
    public static function apply_data( $data, $context ) {
        if ( ! $context instanceof TransformerContext ) {
            return $data;
        }
        $scenario = self::of_options( (array) $context->get_options() );
        if ( ! $scenario ) {
            return $data;
        }
        $data = (array) $data;
        // the content transformer slashes the content for wp_insert_post(): the steps see it as written
        $slashed = isset( $data['post_content'] );
        if ( $slashed ) {
            $data['post_content'] = stripslashes( (string) $data['post_content'] );
        }
        $data = self::run_phase( 'data', $scenario, $data, self::env( $scenario, $data ) );
        if ( $slashed ) {
            $data['post_content'] = addslashes( (string) $data['post_content'] );
        }
        return $data;
    }

    /** the "after" phase: on the created copy (called by ScenarioTransformer) */
    public static function apply_after( TransformerContext $context ): void {
        $scenario = self::of_options( (array) $context->get_options() );
        if ( ! $scenario ) {
            return;
        }
        $did  = $context->get_target()->maybe_switch_to_blog();
        $post = get_post( (int) $context->get_target()->get_post_id(), ARRAY_A );
        if ( $post ) {
            self::run_phase( 'after', $scenario, array( 'post_id' => (int) $post['ID'] ), self::env( $scenario, $post ) );
        }
        \rbDuplicatePost\Contexts\BlogPostContext::maybe_restore_blog( $did );
    }

    public static function run_phase( string $phase, array $scenario, array $subject, array $env ): array {
        $types = self::step_types();
        foreach ( $scenario['steps'] as $step ) {
            if ( empty( $step['on'] ) || ! isset( $types[ $step['type'] ] ) || $types[ $step['type'] ]['phase'] !== $phase ) {
                continue;
            }
            $result = call_user_func( $types[ $step['type'] ]['run'], $step, $subject, $env );
            if ( $phase === 'data' && is_array( $result ) ) {
                $subject = $result;
            }
        }
        return $subject;
    }

    /* ------------------------------------------------------------------ */
    /* the free steps                                                      */
    /* ------------------------------------------------------------------ */

    private const FIELDS = array( 'title' => 'post_title', 'content' => 'post_content', 'excerpt' => 'post_excerpt' );

    /** replace a text in the title, content and excerpt: plain, any letter case, whole words, or (PRO) a pattern */
    public static function run_replace_text( array $step, array $data, array $env ): array {
        $find = self::fill( (string) ( $step['find'] ?? '' ), $env );
        if ( $find === '' ) {
            return $data;
        }
        $replace = self::fill( (string) ( $step['replace'] ?? '' ), $env );
        $mode    = (string) ( $step['mode'] ?? 'plain' );
        $fields  = ! empty( $step['fields'] ) ? (array) $step['fields'] : array_keys( self::FIELDS );
        foreach ( $fields as $field ) {
            $key = self::FIELDS[ $field ] ?? null;
            if ( ! $key || ! isset( $data[ $key ] ) ) {
                continue;
            }
            $data[ $key ] = self::replace( (string) $data[ $key ], $find, $replace, $mode, $key === 'post_content' );
        }
        return $data;
    }

    /**
     * One replacement. In content, only text and attribute values change —
     * never the names of tags and block comments — so the markup stays valid.
     */
    public static function replace( string $subject, string $find, string $replace, string $mode, bool $markup = false ): string {
        switch ( $mode ) {
            case 'nocase':
                $pattern = '/' . preg_quote( $find, '/' ) . '/iu';
                break;
            case 'word':
                $pattern = '/(?<![\p{L}\p{N}_])' . preg_quote( $find, '/' ) . '(?![\p{L}\p{N}_])/iu';
                break;
            case 'regex':
                if ( ! self::regex_allowed() ) {
                    return $subject;
                }
                $pattern = '#' . str_replace( '#', '\#', $find ) . '#u';
                // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a broken pattern changes nothing
                if ( @preg_match( $pattern, '' ) === false ) {
                    return $subject;
                }
                break;
            default:
                if ( ! $markup ) {
                    return str_replace( $find, $replace, $subject );
                }
                $pattern = '/' . preg_quote( $find, '/' ) . '/u';
        }
        $is_regex = $mode === 'regex';
        $apply    = function ( $text ) use ( $pattern, $replace, $is_regex ) {
            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            $out = $is_regex ? @preg_replace( $pattern, $replace, $text ) : preg_replace_callback( $pattern, function () use ( $replace ) { return $replace; }, $text );
            return $out === null ? $text : $out;
        };
        if ( ! $markup ) {
            return $apply( $subject );
        }
        // text between tags, and the values inside tags; tag and attribute names stay
        $parts = preg_split( '/(<!--.*?-->|<[^>]+>)/s', $subject, -1, PREG_SPLIT_DELIM_CAPTURE );
        foreach ( $parts as $i => $part ) {
            if ( $part === '' ) {
                continue;
            }
            if ( $part[0] !== '<' ) {
                $parts[ $i ] = $apply( $part );
                continue;
            }
            // only what people read: alt texts, tooltips, labels — never class, id, style or addresses
            $parts[ $i ] = preg_replace_callback( '/\b([a-zA-Z][-a-zA-Z0-9_:]*)(\s*=\s*)("[^"]*"|\'[^\']*\')/', function ( $m ) use ( $apply ) {
                if ( ! in_array( strtolower( $m[1] ), array( 'alt', 'title', 'aria-label', 'placeholder', 'value', 'content', 'label' ), true ) ) {
                    return $m[0];
                }
                $q = $m[3][0];
                return $m[1] . $m[2] . $q . $apply( substr( $m[3], 1, -1 ) ) . $q;
            }, $part );
            // block attributes in a block comment: <!-- wp:x {"text":"..."} -->
            if ( strpos( $part, '<!--' ) === 0 ) {
                $parts[ $i ] = preg_replace_callback( '/"(content|text|alt|title|caption|label|placeholder|value|heading|description)"(\s*:\s*)"((?:[^"\\\\]|\\\\.)*)"/', function ( $m ) use ( $apply ) {
                    return '"' . $m[1] . '"' . $m[2] . '"' . $apply( $m[3] ) . '"';
                }, $parts[ $i ] );
            }
        }
        return implode( '', $parts );
    }

    /** change link addresses that start with a text: another domain, a moved section */
    public static function run_replace_links( array $step, array $data, array $env ): array {
        $from = trim( self::fill( (string) ( $step['find'] ?? '' ), $env ) );
        $to   = trim( self::fill( (string) ( $step['replace'] ?? '' ), $env ) );
        if ( $from === '' || ! isset( $data['post_content'] ) ) {
            return $data;
        }
        $data['post_content'] = preg_replace_callback(
            '/\b(href|src)\s*=\s*(["\'])(.*?)\2/i',
            function ( $m ) use ( $from, $to ) {
                $url = $m[3];
                if ( stripos( $url, $from ) === 0 ) {
                    $url = $to . substr( $url, strlen( $from ) );
                }
                return $m[1] . '=' . $m[2] . $url . $m[2];
            },
            (string) $data['post_content']
        );
        // block attributes (url, href) in block comments
        $data['post_content'] = preg_replace_callback(
            '/"(url|href|link)"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/',
            function ( $m ) use ( $from, $to ) {
                $url = stripslashes( $m[2] );
                if ( stripos( $url, $from ) !== 0 ) {
                    return $m[0];
                }
                return '"' . $m[1] . '":' . wp_json_encode( $to . substr( $url, strlen( $from ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            },
            $data['post_content']
        );
        return $data;
    }

    public static function run_set_status( array $step, array $data, array $env ): array {
        $status = (string) ( $step['status'] ?? '' );
        if ( in_array( $status, array( 'draft', 'pending', 'private', 'publish' ), true ) ) {
            $data['post_status'] = $status;
        }
        return $data;
    }
}

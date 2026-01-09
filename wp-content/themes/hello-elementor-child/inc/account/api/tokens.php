<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    if (defined('WP_DEBUG') && WP_DEBUG) error_log('YGV: registering token endpoints');

    // GET /yugovote/v1/tokens/state
    register_rest_route('yugovote/v1', '/tokens/state', [
        'methods'  => 'GET',
        'permission_callback' => function(){ return is_user_logged_in(); },
        'callback' => function () {
            $u = get_current_user_id();
            if (function_exists('ygv_tokens')) {
                $w = ygv_tokens()->lazy_refill($u);
                return rest_ensure_response([
                    'tokens' => (int)$w['tokens'],
                    'max'    => (int)$w['max_tokens'],
                    'next_refill_in' => (int) ygv_tokens()->seconds_to_next_refill($u),
                ]);
            }
            // Fallback if service missing
            return rest_ensure_response(['tokens'=>0,'max'=>48,'next_refill_in'=>0,'note'=>'token service missing']);
        }
    ]);

    // POST /yugovote/v1/tokens/spend
    register_rest_route('yugovote/v1', '/tokens/spend', [
        'methods'  => 'POST',
        'permission_callback' => function(){ return is_user_logged_in(); },
        'callback' => function (WP_REST_Request $req) {
            $u = get_current_user_id();
            $cost = max(1, (int) ($req->get_param('cost') ?? 8));

            if (function_exists('ygv_tokens')) {
                ygv_tokens()->lazy_refill($u);
                if (!ygv_tokens()->spend_tokens_atomic($u, $cost)) {
                    return new WP_Error('insufficient_tokens', __('Nemate dovoljno tokena.', 'hello-elementor-child'), [
                        'status' => 403,
                        'next_refill_in' => ygv_tokens()->seconds_to_next_refill($u),
                    ]);
                }
                $w = ygv_tokens()->get_wallet($u);
                return rest_ensure_response([
                    'spent' => $cost,
                    'left'  => (int)$w['tokens'],
                    'max'   => (int)$w['max_tokens'],
                ]);
            }
            // Fallback: pretend success so UI can proceed during dev
            return rest_ensure_response(['spent'=>$cost,'left'=>0,'max'=>48,'note'=>'token service missing']);
        }
    ]);
});

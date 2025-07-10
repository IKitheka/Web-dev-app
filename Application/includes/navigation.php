<?php
if (!function_exists('get_current_user_type')) {
    require_once __DIR__ . '/../database/auth_helpers.php';
}
function get_navigation_items($current_page = '') {
    $user_type = get_current_user_type();
    $is_logged_in = is_authenticated();
    $base_items = [
        'home' => [
            'url' => '../../index.php',
            'label' => 'INTERN CONNECT'
        ]
    ];
    $role_items = [];
    $root = dirname(__DIR__);
    $auth_dir = $root . '/Authentication/';
    $forms_dir = $root . '/Forms/';
    $profiles_dir = $root . '/Profiles/';
    $dashboards_dir = $root . '/Dashboards/';
    $apps_dir = $root . '/Applications/';
    if (!$is_logged_in) {
        $role_items = [];
        if (file_exists($auth_dir . 'login.php')) {
            $role_items['login'] = [
                'url' => '../Authentication/login.php',
                'label' => 'Login'
            ];
        }
        if (file_exists($auth_dir . 'student_register.php')) {
            $role_items['register_student'] = [
                'url' => '../Authentication/student_register.php',
                'label' => 'Student Register'
            ];
        }
        if (file_exists($auth_dir . 'employer_register.php')) {
            $role_items['register_employer'] = [
                'url' => '../Authentication/employer_register.php',
                'label' => 'Employer Register'
            ];
        }
    } else {
        switch ($user_type) {
            case 'student':
                $role_items = [];
                if (file_exists($dashboards_dir . '/student_dashboard.php')) {
                    $role_items['dashboard'] = [
                        'url' => '../Dashboards/student_dashboard.php',
                        'label' => 'Dashboard'
                    ];
                }
                if (file_exists($forms_dir . 'browse_internship.php')) {
                    $role_items['browse'] = [
                        'url' => '../Forms/browse_internship.php',
                        'label' => 'Browse Internships'
                    ];
                }
                if (file_exists($forms_dir . 'my_applications.php')) {
                    $role_items['applications'] = [
                        'url' => '../Forms/my_applications.php',
                        'label' => 'My Applications'
                    ];
                }
                if (file_exists($profiles_dir . 'student_profile.php')) {
                    $role_items['profile'] = [
                        'url' => '../Profiles/student_profile.php',
                        'label' => 'Profile'
                    ];
                }
                break;
            case 'employer':
                $role_items = [];
                if (file_exists($dashboards_dir . '/employer_dashboard.php')) {
                    $role_items['dashboard'] = [
                        'url' => '../Dashboards/employer_dashboard.php',
                        'label' => 'Dashboard'
                    ];
                }
                if (file_exists($forms_dir . 'browse_internship.php')) {
                    $role_items['browse'] = [
                        'url' => '../Forms/browse_internship.php',
                        'label' => 'Browse Internships'
                    ];
                }
                if (file_exists($forms_dir . 'create_job.php')) {
                    $role_items['create_job'] = [
                        'url' => '../Forms/create_job.php',
                        'label' => 'Post Job'
                    ];
                }
                if (file_exists($apps_dir . 'view_applicants.php')) {
                    $role_items['applicants'] = [
                        'url' => '../Applications/view_applicants.php',
                        'label' => 'Applicants'
                    ];
                }
                if (file_exists($root . '/Results/complete_internship.php')) {
                    $role_items['complete_internships'] = [
                        'url' => '../Results/complete_internship.php',
                        'label' => 'Complete Internships'
                    ];
                }
                if (file_exists($profiles_dir . 'employer_profile.php')) {
                    $role_items['profile'] = [
                        'url' => '../Profiles/employer_profile.php',
                        'label' => 'Profile'
                    ];
                }
                break;
            case 'admin':
                $role_items = [];
                if (file_exists($dashboards_dir . '/admin_dashboard.php')) {
                    $role_items['dashboard'] = [
                        'url' => '../Dashboards/admin_dashboard.php',
                        'label' => 'Dashboard'
                    ];
                }
                if (file_exists($forms_dir . 'employer_manager.php')) {
                    $role_items['employers'] = [
                        'url' => '../Forms/employer_manager.php',
                        'label' => 'Employers'
                    ];
                }
                if (file_exists($forms_dir . 'student_manager.php')) {
                    $role_items['students'] = [
                        'url' => '../Forms/student_manager.php',
                        'label' => 'Students'
                    ];
                }
                if (file_exists($root . '/Results/complete_internship.php')) {
                    $role_items['complete_internships'] = [
                        'url' => '../Results/complete_internship.php',
                        'label' => 'Complete Internships'
                    ];
                }
                if (file_exists($root . '/Certificates/certificate_list.php')) {
                    $role_items['certificates'] = [
                        'url' => '../Certificates/certificate_list.php',
                        'label' => 'Certificates'
                    ];
                }
                if (file_exists($profiles_dir . 'admin_profile.php')) {
                    $role_items['profile'] = [
                        'url' => '../Profiles/admin_profile.php',
                        'label' => 'Profile'
                    ];
                }
                break;
        }
    }
    $logout_item = [];
    if ($is_logged_in && file_exists($root . '/logout.php')) {
        $logout_item = [
            'logout' => [
                'url' => '../logout.php',
                'label' => 'Log out',
                'style' => 'margin-left: auto;'
            ]
        ];
    }
    return array_merge($base_items, $role_items, $logout_item);
}
function render_navigation($current_page = '') {
    $nav_items = get_navigation_items($current_page);
    $user_type = get_current_user_type();
    $user_id = get_current_user_id();
    $html = '<nav class="nav">' . "\n";
    foreach ($nav_items as $key => $item) {
        $active_class = ($key === $current_page) ? ' class="active"' : '';
        $style = isset($item['style']) ? ' style="' . $item['style'] . '"' : '';
        $html .= sprintf(
            '    <a href="%s"%s%s>%s</a>' . "\n",
            htmlspecialchars($item['url']),
            $active_class,
            $style,
            htmlspecialchars($item['label'])
        );
    }
    $html .= '</nav>' . "\n";
    return $html;
}
function render_user_context() {
    $user_type = get_current_user_type();
    $user_id = get_current_user_id();
    if (!$user_type || !$user_id) {
        return '';
    }
    $type_icons = [
        'student' => '🎓',
        'employer' => '🏢',
        'admin' => '⚙️'
    ];
    $icon = $type_icons[$user_type] ?? '👤';
    $short_id = substr($user_id, 0, 8) . '...';
    return sprintf(
        '<div class="user-context">
            <span class="user-type">%s %s</span>
            <span class="user-id">ID: %s</span>
            <span class="auth-status">✅ Authenticated</span>
        </div>',
        $icon,
        htmlspecialchars(ucfirst($user_type)),
        htmlspecialchars($short_id)
    );
}
function render_message($message, $type = 'info') {
    if (empty($message)) {
        return '';
    }
    $icons = [
        'success' => '✅',
        'error' => '❌',
        'info' => 'ℹ️',
        'warning' => '⚠️'
    ];
    $icon = $icons[$type] ?? 'ℹ️';
    return sprintf(
        '<div class="message message-%s">
            <span class="message-icon">%s</span>
            <span class="message-text">%s</span>
        </div>',
        htmlspecialchars($type),
        $icon,
        htmlspecialchars($message)
    );
}
function render_page_header($title, $subtitle = '') {
    $user_context = render_user_context();
    $html = sprintf('<header class="header">%s</header>' . "\n", htmlspecialchars($title));
    if ($subtitle) {
        $html .= sprintf('<div class="page-subtitle">%s</div>' . "\n", htmlspecialchars($subtitle));
    }
    return $html;
}
function get_navigation_styles() {
    return '
    <style>
        .user-context {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin: 0 auto 20px;
            font-size: 0.9rem;
            max-width: 1200px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .user-type {
            font-weight: 600;
        }
        .user-id {
            opacity: 0.8;
            font-family: monospace;
        }
        .auth-status {
            color: #00ff88;
            font-weight: 500;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 15px auto;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 1200px;
            animation: messageSlideIn 0.3s ease-out;
        }
        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .message-success {
            background: rgba(0, 255, 0, 0.1);
            color: #00ff88;
            border: 1px solid rgba(0, 255, 0, 0.3);
        }
        .message-error {
            background: rgba(255, 0, 0, 0.1);
            color: #ff6b6b;
            border: 1px solid rgba(255, 0, 0, 0.3);
        }
        .message-info {
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
            border: 1px solid rgba(0, 123, 255, 0.3);
        }
        .message-warning {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        .nav a {
            transition: all 0.3s ease;
            position: relative;
        }
        .nav a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        }
        .nav a.active::after {
            content: "";
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 2px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 1px;
        }
        .page-subtitle {
            text-align: center;
            opacity: 0.8;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .user-context {
                flex-direction: column;
                gap: 8px;
                text-align: center;
            }
            .nav {
                flex-wrap: wrap;
                gap: 10px;
            }
            .nav a {
                flex: 1;
                min-width: 120px;
                text-align: center;
            }
        }
    </style>';
}
?>

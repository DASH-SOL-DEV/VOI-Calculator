<?php
/**
 * VOI Advanced Calculator - Phase 2
 * Allows complete customization of all ROI parameters
 * 
 * Save as: includes/class-voi-advanced-calculator.php
 */

class VOI_Advanced_Calculator {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
    }
    
    public function init() {
        // AJAX handlers
        add_action('wp_ajax_voi_save_step', [$this, 'save_calculator_step']);
        add_action('wp_ajax_nopriv_voi_save_step', [$this, 'save_calculator_step']);
        
        add_action('wp_ajax_voi_calculate_preview', [$this, 'calculate_preview']);
        add_action('wp_ajax_nopriv_voi_calculate_preview', [$this, 'calculate_preview']);
        
        add_action('wp_ajax_voi_submit_advanced', [$this, 'submit_advanced_calculator']);
        add_action('wp_ajax_nopriv_voi_submit_advanced', [$this, 'submit_advanced_calculator']);
        
        add_action('wp_ajax_voi_get_step_config', [$this, 'get_step_configuration']);
        add_action('wp_ajax_nopriv_voi_get_step_config', [$this, 'get_step_configuration']);
    }
    
    /**
     * Get step configuration for frontend
     */
    public function get_step_configuration() {
        wp_send_json_success(['steps' => self::get_calculator_steps()]);
    }
    
    /**
     * Define all calculator steps and their fields
     */
    public static function get_calculator_steps() {
        return [
            1 => [
                'title' => 'Environment Setup',
                'description' => 'Tell us about your current storage environment',
                'fields' => [
                    'total_tb' => [
                        'label' => 'Total Storage Space (TB)',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => 'e.g., 10000',
                        'help' => 'Total amount of storage in your environment',
                        'min' => 1
                    ],
                    'cost_per_tb' => [
                        'label' => 'Cost per TB ($)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 500,
                        'placeholder' => 'e.g., 500',
                        'help' => 'Average annual cost per terabyte of storage',
                        'min' => 1
                    ],
                    'total_vms' => [
                        'label' => 'Total Number of VMs',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => 'e.g., 1000',
                        'help' => 'Total virtual machines in your environment',
                        'min' => 1
                    ]
                ]
            ],
            
            2 => [
                'title' => 'Employee Cost Structure',
                'description' => 'Configure your employee cost parameters',
                'fields' => [
                    'employee_yearly_cost' => [
                        'label' => 'Fully Burdened Annual Employee Cost ($)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 150000,
                        'placeholder' => 'e.g., 150000',
                        'help' => 'Total yearly cost including salary, benefits, overhead per employee',
                        'min' => 1
                    ],
                    'work_hours_yearly' => [
                        'label' => 'Work Hours per Year',
                        'type' => 'number',
                        'required' => true,
                        'default' => 1880,
                        'placeholder' => 'e.g., 1880',
                        'help' => 'Total billable/productive hours per employee per year (typically 1880)',
                        'min' => 1
                    ]
                ]
            ],
            
            3 => [
                'title' => 'Storage Optimization Potential',
                'description' => 'Set your expected storage efficiency improvements',
                'fields' => [
                    'reuse_orphaned_percent' => [
                        'label' => 'Reuse of Orphaned Space (%)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 2.0,
                        'step' => 0.1,
                        'placeholder' => 'e.g., 2.0',
                        'help' => 'Percentage of total storage that can be reclaimed from orphaned/unused space',
                        'min' => 0,
                        'max' => 50
                    ],
                    'improved_processes_percent' => [
                        'label' => 'Process Improvement Savings (%)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 2.0,
                        'step' => 0.1,
                        'placeholder' => 'e.g., 2.0',
                        'help' => 'Storage cost savings from improved operational processes',
                        'min' => 0,
                        'max' => 50
                    ],
                    'buying_accuracy_percent' => [
                        'label' => 'Purchasing Accuracy Improvement (%)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 1.0,
                        'step' => 0.1,
                        'placeholder' => 'e.g., 1.0',
                        'help' => 'Cost avoidance from more accurate storage capacity planning and purchasing',
                        'min' => 0,
                        'max' => 25
                    ]
                ]
            ],
            
            4 => [
                'title' => 'Personnel Time Savings',
                'description' => 'How much time will VOI save your team each week?',
                'fields' => [
                    'time_building_reports' => [
                        'label' => 'Time Building Reports (hours/week)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 4,
                        'step' => 0.5,
                        'placeholder' => 'e.g., 4',
                        'help' => 'Hours per week spent manually creating storage reports',
                        'min' => 0,
                        'max' => 40
                    ],
                    'time_planning' => [
                        'label' => 'Time on Capacity Planning (hours/week)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 2,
                        'step' => 0.5,
                        'placeholder' => 'e.g., 2',
                        'help' => 'Hours per week spent on manual capacity planning activities',
                        'min' => 0,
                        'max' => 40
                    ],
                    'modeling_trends' => [
                        'label' => 'Trend Analysis & Modeling (hours/week)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 2,
                        'step' => 0.5,
                        'placeholder' => 'e.g., 2',
                        'help' => 'Hours per week analyzing storage usage trends and growth patterns',
                        'min' => 0,
                        'max' => 40
                    ],
                    'problem_resolution' => [
                        'label' => 'Problem Resolution (hours/week)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 4,
                        'step' => 0.5,
                        'placeholder' => 'e.g., 4',
                        'help' => 'Hours per week troubleshooting storage-related issues',
                        'min' => 0,
                        'max' => 40
                    ],
                    'capacity_reporting' => [
                        'label' => 'Data Collection for Reports (hours/week)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 4,
                        'step' => 0.5,
                        'placeholder' => 'e.g., 4',
                        'help' => 'Hours per week collecting and aggregating capacity data',
                        'min' => 0,
                        'max' => 40
                    ],
                    'service_improvement' => [
                        'label' => 'Service Optimization (hours/week)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 6,
                        'step' => 0.5,
                        'placeholder' => 'e.g., 6',
                        'help' => 'Hours per week on storage service improvements and optimizations',
                        'min' => 0,
                        'max' => 40
                    ],
                    'automation_tasks' => [
                        'label' => 'Manual Tasks for Automation (hours/week)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 4,
                        'step' => 0.5,
                        'placeholder' => 'e.g., 4',
                        'help' => 'Hours per week on repetitive tasks that could be automated',
                        'min' => 0,
                        'max' => 40
                    ]
                ]
            ],
            
            5 => [
                'title' => 'Business Impact & Costs',
                'description' => 'Configure operational costs and VOI investment',
                'fields' => [
                    'outage_avoidance_savings' => [
                        'label' => 'Annual Outage Avoidance Value ($)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 250000,
                        'placeholder' => 'e.g., 250000',
                        'help' => 'Estimated annual value of prevented storage-related outages and downtime',
                        'min' => 0
                    ],
                    'voi_annual_cost' => [
                        'label' => 'VOI Annual License Cost ($)',
                        'type' => 'number',
                        'required' => true,
                        'default' => 150000,
                        'placeholder' => 'e.g., 150000',
                        'help' => 'Annual cost of Visual One Intelligence license for your environment',
                        'min' => 1
                    ]
                ]
            ],
            
            6 => [
                'title' => 'Contact Information',
                'description' => 'Your details for the personalized analysis report',
                'fields' => [
                    'full_name' => [
                        'label' => 'Full Name',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'e.g., John Doe',
                        'help' => 'Your first and last name for the report',
                        'maxlength' => 100
                    ],
                    'email' => [
                        'label' => 'Email Address',
                        'type' => 'email',
                        'required' => true,
                        'placeholder' => 'e.g., john@company.com',
                        'help' => 'Email address to receive your custom ROI analysis',
                        'maxlength' => 100
                    ],
                    'company_name' => [
                        'label' => 'Company Name',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'e.g., ACME Corporation',
                        'help' => 'Your organization name for the report header',
                        'maxlength' => 100
                    ],
                    'company_url' => [
                        'label' => 'Company Website',
                        'type' => 'url',
                        'required' => false,
                        'placeholder' => 'e.g., https://company.com',
                        'help' => 'Your company website URL (optional)',
                        'maxlength' => 200
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Calculate comprehensive ROI based on user inputs
     */
    public static function calculate_roi($data) {
        // Extract and validate input values
        $total_tb = max(0, floatval($data['total_tb'] ?? 0));
        $cost_per_tb = max(0, floatval($data['cost_per_tb'] ?? 500));
        $employee_yearly_cost = max(0, floatval($data['employee_yearly_cost'] ?? 150000));
        $work_hours_yearly = max(1, floatval($data['work_hours_yearly'] ?? 1880));
        
        // Calculate effective hourly rate
        $effective_hourly_rate = $employee_yearly_cost / $work_hours_yearly;
        
        // Cost Avoidance Calculations
        $reuse_percent = max(0, min(50, floatval($data['reuse_orphaned_percent'] ?? 2.0))) / 100;
        $process_percent = max(0, min(50, floatval($data['improved_processes_percent'] ?? 2.0))) / 100;
        $buying_percent = max(0, min(25, floatval($data['buying_accuracy_percent'] ?? 1.0))) / 100;
        
        $reuse_space_savings = $reuse_percent * $total_tb * $cost_per_tb;
        $improved_processes_savings = $process_percent * $total_tb * $cost_per_tb;
        $improve_buying_accuracy_savings = $buying_percent * $total_tb * $cost_per_tb;
        
        // Personnel Savings (weekly hours * 52 weeks * hourly rate)
        $time_building_reports = max(0, floatval($data['time_building_reports'] ?? 4));
        $time_planning = max(0, floatval($data['time_planning'] ?? 2));
        $modeling_trends = max(0, floatval($data['modeling_trends'] ?? 2));
        $problem_resolution = max(0, floatval($data['problem_resolution'] ?? 4));
        $capacity_reporting = max(0, floatval($data['capacity_reporting'] ?? 4));
        $service_improvement = max(0, floatval($data['service_improvement'] ?? 6));
        $automation_tasks = max(0, floatval($data['automation_tasks'] ?? 4));
        
        $time_building_reports_savings = $time_building_reports * 52 * $effective_hourly_rate;
        $time_planning_savings = $time_planning * 52 * $effective_hourly_rate;
        $modeling_trends_savings = $modeling_trends * 52 * $effective_hourly_rate;
        $improved_problem_resolution_savings = $problem_resolution * 52 * $effective_hourly_rate;
        $capacity_report_collection_savings = $capacity_reporting * 52 * $effective_hourly_rate;
        $service_improvement_savings = $service_improvement * 52 * $effective_hourly_rate;
        $automation_savings = $automation_tasks * 52 * $effective_hourly_rate;
        
        // Operational Efficiencies
        $outage_avoidance_savings = max(0, floatval($data['outage_avoidance_savings'] ?? 250000));
        
        // Calculate category totals
        $total_cost_avoidance = $reuse_space_savings + $improved_processes_savings + $improve_buying_accuracy_savings;
        $total_personnel_savings = $time_building_reports_savings + $time_planning_savings + $modeling_trends_savings +
                                 $improved_problem_resolution_savings + $capacity_report_collection_savings +
                                 $service_improvement_savings + $automation_savings;
        $total_operational_savings = $outage_avoidance_savings;
        
        // Grand totals
        $total_annual_savings = $total_cost_avoidance + $total_personnel_savings + $total_operational_savings;
        $voi_annual_cost = max(1, floatval($data['voi_annual_cost'] ?? 150000));
        $net_benefit = $total_annual_savings - $voi_annual_cost;
        $payback_months = $total_annual_savings > 0 ? ($voi_annual_cost / $total_annual_savings) * 12 : 999;
        $annual_roi = $voi_annual_cost > 0 ? ($net_benefit / $voi_annual_cost) * 100 : 0;
        
        return [
            'input_data' => $data,
            'calculated_hourly_rate' => $effective_hourly_rate,
            'cost_avoidance' => [
                'reuse_space_savings' => $reuse_space_savings,
                'improved_processes_savings' => $improved_processes_savings,
                'improve_buying_accuracy_savings' => $improve_buying_accuracy_savings,
                'total' => $total_cost_avoidance,
                'breakdown' => [
                    'reuse_tb' => $reuse_percent * $total_tb,
                    'process_tb' => $process_percent * $total_tb,
                    'buying_tb' => $buying_percent * $total_tb
                ]
            ],
            'personnel_savings' => [
                'time_building_reports_savings' => $time_building_reports_savings,
                'time_planning_savings' => $time_planning_savings,
                'modeling_trends_savings' => $modeling_trends_savings,
                'improved_problem_resolution_savings' => $improved_problem_resolution_savings,
                'capacity_report_collection_savings' => $capacity_report_collection_savings,
                'service_improvement_savings' => $service_improvement_savings,
                'automation_savings' => $automation_savings,
                'total' => $total_personnel_savings,
                'total_weekly_hours' => $time_building_reports + $time_planning + $modeling_trends +
                                      $problem_resolution + $capacity_reporting + $service_improvement + $automation_tasks,
                'total_yearly_hours' => ($time_building_reports + $time_planning + $modeling_trends +
                                       $problem_resolution + $capacity_reporting + $service_improvement + $automation_tasks) * 52
            ],
            'operational_savings' => [
                'outage_avoidance_savings' => $outage_avoidance_savings,
                'total' => $total_operational_savings
            ],
            'summary' => [
                'total_annual_savings' => $total_annual_savings,
                'voi_annual_cost' => $voi_annual_cost,
                'net_benefit' => $net_benefit,
                'payback_months' => $payback_months,
                'annual_roi' => $annual_roi,
                'break_even_point' => $payback_months <= 12 ? 'Less than 1 year' : 
                                    ($payback_months <= 24 ? 'Less than 2 years' : 
                                    ($payback_months <= 36 ? 'Less than 3 years' : 'More than 3 years'))
            ]
        ];
    }
    
    /**
     * Save step data to session
     */
    public function save_calculator_step() {
        if (!check_ajax_referer('voi_calculator_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.']);
            return;
        }
        
        $step = intval($_POST['step'] ?? 0);
        $data = $_POST['data'] ?? [];
        
        // Initialize session data if needed
        if (!isset($_SESSION['voi_advanced_data'])) {
            $_SESSION['voi_advanced_data'] = [];
        }
        
        // Save step data
        $_SESSION['voi_advanced_data']['step_' . $step] = $data;
        $_SESSION['voi_advanced_data']['current_step'] = $step;
        $_SESSION['voi_advanced_data']['last_updated'] = time();
        
        wp_send_json_success(['message' => 'Step data saved successfully', 'step' => $step]);
    }
    
    /**
     * Calculate preview for current state
     */
    public function calculate_preview() {
        if (!check_ajax_referer('voi_calculator_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.']);
            return;
        }
        
        $current_data = $_POST['data'] ?? [];
        
        // Merge with existing session data
        $all_data = $current_data;
        if (isset($_SESSION['voi_advanced_data'])) {
            foreach ($_SESSION['voi_advanced_data'] as $key => $step_data) {
                if (strpos($key, 'step_') === 0 && is_array($step_data)) {
                    $all_data = array_merge($all_data, $step_data);
                }
            }
            // Current data takes precedence
            $all_data = array_merge($all_data, $current_data);
        }
        
        try {
            $calculations = self::calculate_roi($all_data);
            wp_send_json_success(['calculations' => $calculations]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Calculation failed: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Submit final advanced calculator
     */
    public function submit_advanced_calculator() {
        if (!check_ajax_referer('voi_calculator_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed.']);
            return;
        }
        
        // Collect all data from session
        $form_data = [];
        if (isset($_SESSION['voi_advanced_data'])) {
            foreach ($_SESSION['voi_advanced_data'] as $key => $step_data) {
                if (strpos($key, 'step_') === 0 && is_array($step_data)) {
                    $form_data = array_merge($form_data, $step_data);
                }
            }
        }
        
        // Add final step data
        $final_data = $_POST['data'] ?? [];
        $form_data = array_merge($form_data, $final_data);
        
        // Calculate final ROI
        $calculations = self::calculate_roi($form_data);
        
        // Save to advanced submissions table
        global $wpdb;
        $table_name = $wpdb->prefix . 'voi_advanced_submissions';
        
        $db_data = [
            'time' => current_time('mysql'),
            'full_name' => sanitize_text_field($form_data['full_name'] ?? ''),
            'email' => sanitize_email($form_data['email'] ?? ''),
            'company_name' => sanitize_text_field($form_data['company_name'] ?? ''),
            'company_url' => esc_url_raw($form_data['company_url'] ?? ''),
            'input_data' => json_encode($form_data),
            'calculations' => json_encode($calculations),
            'total_annual_savings' => $calculations['summary']['total_annual_savings'],
            'annual_roi' => $calculations['summary']['annual_roi'],
            'payback_months' => $calculations['summary']['payback_months']
        ];
        
        $result = $wpdb->insert($table_name, $db_data);
        $submission_id = $wpdb->insert_id;
        
        if ($result) {
            // Clear session data
            unset($_SESSION['voi_advanced_data']);
            $_SESSION['voi_advanced_submission_id'] = $submission_id;
            
            // Trigger integrations (HubSpot, Email) - without PDF
            do_action('voi_advanced_form_submitted', $form_data, null, $submission_id, $calculations);
            
            wp_send_json_success([
                'submission_id' => $submission_id,
                'calculations' => $calculations,
                'message' => 'Advanced ROI analysis completed successfully!'
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to save submission data.']);
        }
    }
}
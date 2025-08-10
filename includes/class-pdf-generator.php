<?php
/**
 * VOI Calculator PDF Generator
 * Generates ROI worksheet PDF matching the Visual Storage Intelligence format
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VOI_PDF_Generator {
    
    private $storage_tb;
    private $vm_count;
    private $company_name;
    private $calculations;
    
    public function __construct($storage_tb, $vm_count, $company_name) {
        $this->storage_tb = floatval($storage_tb);
        $this->vm_count = intval($vm_count);
        $this->company_name = $company_name;
        $this->calculate_roi();
    }
    
    private function calculate_roi() {
        // Base assumptions (industry standards)
        $cost_per_tb = 500;
        $employee_yearly_cost = 150000;
        $work_hours_yearly = 1880;
        $hourly_rate = $employee_yearly_cost / $work_hours_yearly; // $79.79
        
        // Calculate total space based on input (scale from sample)
        $total_space = $this->storage_tb;
        
        // Cost Avoidance calculations
        $reuse_orphaned_percentage = 2.0;
        $improved_processes_percentage = 2.0;
        $buying_accuracy_percentage = 1.0;
        
        $reuse_orphaned_space = ($reuse_orphaned_percentage / 100) * $total_space;
        $improved_processes_space = ($improved_processes_percentage / 100) * $total_space;
        $buying_accuracy_space = ($buying_accuracy_percentage / 100) * $total_space;
        
        $reuse_orphaned_savings = $reuse_orphaned_space * $cost_per_tb;
        $improved_processes_savings = $improved_processes_space * $cost_per_tb;
        $buying_accuracy_savings = $buying_accuracy_space * $cost_per_tb;
        
        // Personnel Savings (hours per week to yearly savings)
        $personnel_activities = [
            'building_reports' => ['weekly_hours' => 4, 'yearly_hours' => 208],
            'planning' => ['weekly_hours' => 2, 'yearly_hours' => 104],
            'modeling_trends' => ['weekly_hours' => 2, 'yearly_hours' => 104],
            'problem_resolution' => ['weekly_hours' => 4, 'yearly_hours' => 208],
            'new_reports' => ['weekly_hours' => 0, 'yearly_hours' => 0],
            'capacity_collection' => ['weekly_hours' => 4, 'yearly_hours' => 208],
            'service_improvement' => ['weekly_hours' => 6, 'yearly_hours' => 312],
            'automation' => ['weekly_hours' => 4, 'yearly_hours' => 208],
            'ticket_reduction' => ['weekly_hours' => 0, 'yearly_hours' => 0],
            'manage_srm' => ['weekly_hours' => 0, 'yearly_hours' => 0]
        ];
        
        $total_personnel_savings = 0;
        foreach ($personnel_activities as $activity => $hours) {
            $personnel_activities[$activity]['annual_savings'] = $hours['yearly_hours'] * $hourly_rate;
            $total_personnel_savings += $personnel_activities[$activity]['annual_savings'];
        }
        
        // Operational Efficiencies
        $outage_avoidance = 250000; // Fixed amount
        $data_modeling_accuracy = 0; // Not specified
        
        // Calculate totals
        $total_cost_avoidance = $reuse_orphaned_savings + $improved_processes_savings + $buying_accuracy_savings;
        $total_operational = $outage_avoidance;
        $total_annual_savings = $total_cost_avoidance + $total_personnel_savings + $total_operational;
        
        // VSI Annual Cost (scale based on storage size)
        $base_vsi_cost = 150000;
        // Scale cost based on storage size (larger environments cost more)
        $vsi_annual_cost = $base_vsi_cost + (($total_space / 1000) * 5000); // Add $5k per 1000TB
        
        // ROI Calculations
        $payback_months = ($vsi_annual_cost / $total_annual_savings) * 12;
        $annual_roi = (($total_annual_savings - $vsi_annual_cost) / $vsi_annual_cost) * 100;
        
        $this->calculations = [
            'assumptions' => [
                'total_space' => $total_space,
                'cost_per_tb' => $cost_per_tb,
                'employee_yearly_cost' => $employee_yearly_cost,
                'work_hours_yearly' => $work_hours_yearly,
                'hourly_rate' => $hourly_rate
            ],
            'cost_avoidance' => [
                'reuse_orphaned' => [
                    'percentage' => $reuse_orphaned_percentage,
                    'space_savings' => $reuse_orphaned_space,
                    'annual_savings' => $reuse_orphaned_savings
                ],
                'improved_processes' => [
                    'percentage' => $improved_processes_percentage,
                    'space_savings' => $improved_processes_space,
                    'annual_savings' => $improved_processes_savings
                ],
                'buying_accuracy' => [
                    'percentage' => $buying_accuracy_percentage,
                    'space_savings' => $buying_accuracy_space,
                    'annual_savings' => $buying_accuracy_savings
                ]
            ],
            'personnel_savings' => $personnel_activities,
            'operational_efficiencies' => [
                'outage_avoidance' => $outage_avoidance,
                'data_modeling_accuracy' => $data_modeling_accuracy
            ],
            'totals' => [
                'total_cost_avoidance' => $total_cost_avoidance,
                'total_personnel_savings' => $total_personnel_savings,
                'total_operational' => $total_operational,
                'total_annual_savings' => $total_annual_savings,
                'vsi_annual_cost' => $vsi_annual_cost,
                'payback_months' => $payback_months,
                'annual_roi' => $annual_roi
            ]
        ];
    }
    
    public function generate_pdf() {
        // Check if we're in safe range
        $roi = $this->calculations['totals']['annual_roi'];
        $is_safe_range = ($roi >= 50 && $roi <= 1000); // Define safe ROI range
        
        if (!$is_safe_range) {
            // Log for manual review
            error_log("VOI Calculator: ROI outside safe range - {$roi}% for {$this->company_name}");
        }
        
        // For now, return HTML that can be converted to PDF
        // In production, you'd use a library like TCPDF, DOMPDF, or mPDF
        return $this->generate_html_report();
    }
    
    private function generate_html_report() {
        $calc = $this->calculations;
        $date = date('m/d/Y');
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Visual Storage Intelligence ROI Worksheet</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    font-size: 12px;
                    line-height: 1.2;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .company-logo {
                    float: right;
                    margin-bottom: 10px;
                }
                .title {
                    font-size: 16px;
                    font-weight: bold;
                    margin: 10px 0;
                }
                .subtitle {
                    font-size: 14px;
                    margin: 5px 0;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                .main-table {
                    border: 2px solid #000;
                }
                .section-header {
                    background-color: #9BC4E4;
                    font-weight: bold;
                    padding: 5px;
                    border: 1px solid #000;
                }
                .subsection-header {
                    background-color: #D4E4F7;
                    font-weight: bold;
                    padding: 5px;
                    border: 1px solid #000;
                }
                td {
                    padding: 3px 5px;
                    border: 1px solid #000;
                    vertical-align: top;
                }
                .number {
                    text-align: right;
                }
                .currency {
                    text-align: right;
                }
                .percentage {
                    text-align: center;
                }
                .final-results {
                    background-color: #E4F2FF;
                    font-weight: bold;
                }
                .logo-text {
                    color: #4A90E2;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class="company-logo">
                <div class="logo-text">visual<span style="color: #8BC34A;">storage</span><br>INTELLIGENCE</div>
            </div>
            
            <div class="header">
                <div class="title">Visual Storage Intelligence ROI Worksheet</div>
                <div class="subtitle">Prepared for: <?php echo htmlspecialchars($this->company_name); ?> (Estimated Value)</div>
                <div class="subtitle"><?php echo $date; ?></div>
            </div>
            
            <table class="main-table">
                <!-- Assumptions Section -->
                <tr class="section-header">
                    <td colspan="4">Assumptions</td>
                </tr>
                <tr class="subsection-header">
                    <td colspan="4">Current Environment:</td>
                </tr>
                <tr>
                    <td>Total Space (TB)</td>
                    <td class="number"><?php echo number_format($calc['assumptions']['total_space']); ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td>Cost per TB</td>
                    <td class="currency">$<?php echo number_format($calc['assumptions']['cost_per_tb']); ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="4"><strong>Employee Cost:</strong></td>
                </tr>
                <tr>
                    <td>Fully burdened yearly cost</td>
                    <td class="currency">$<?php echo number_format($calc['assumptions']['employee_yearly_cost']); ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td>Work hours yearly</td>
                    <td class="number"><?php echo number_format($calc['assumptions']['work_hours_yearly']); ?></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td>Hourly rate</td>
                    <td class="currency">$<?php echo number_format($calc['assumptions']['hourly_rate'], 0); ?></td>
                    <td colspan="2"></td>
                </tr>
                
                <!-- Cost Avoidance Section -->
                <tr class="section-header">
                    <td>Cost Avoidance</td>
                    <td>% of Total Space</td>
                    <td>Space Savings (TB)</td>
                    <td>Annual Savings</td>
                </tr>
                <tr>
                    <td>Reuse of Orphaned Space</td>
                    <td class="percentage"><?php echo $calc['cost_avoidance']['reuse_orphaned']['percentage']; ?>%</td>
                    <td class="number"><?php echo number_format($calc['cost_avoidance']['reuse_orphaned']['space_savings']); ?></td>
                    <td class="currency">$<?php echo number_format($calc['cost_avoidance']['reuse_orphaned']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>Improved Processes</td>
                    <td class="percentage"><?php echo $calc['cost_avoidance']['improved_processes']['percentage']; ?>%</td>
                    <td class="number"><?php echo number_format($calc['cost_avoidance']['improved_processes']['space_savings']); ?></td>
                    <td class="currency">$<?php echo number_format($calc['cost_avoidance']['improved_processes']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>Improve Buying Accuracy</td>
                    <td class="percentage"><?php echo $calc['cost_avoidance']['buying_accuracy']['percentage']; ?>%</td>
                    <td class="number"><?php echo number_format($calc['cost_avoidance']['buying_accuracy']['space_savings']); ?></td>
                    <td class="currency">$<?php echo number_format($calc['cost_avoidance']['buying_accuracy']['annual_savings'], 2); ?></td>
                </tr>
                
                <!-- Personnel Savings Section -->
                <tr class="section-header">
                    <td>Personnel Savings</td>
                    <td>Hrs/Weekly</td>
                    <td>Hrs/Yearly</td>
                    <td>Annual Savings</td>
                </tr>
                <tr>
                    <td>Time spent building reports</td>
                    <td class="number"><?php echo $calc['personnel_savings']['building_reports']['weekly_hours']; ?></td>
                    <td class="number"><?php echo $calc['personnel_savings']['building_reports']['yearly_hours']; ?></td>
                    <td class="currency">$<?php echo number_format($calc['personnel_savings']['building_reports']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>Time Spent planning</td>
                    <td class="number"><?php echo $calc['personnel_savings']['planning']['weekly_hours']; ?></td>
                    <td class="number"><?php echo $calc['personnel_savings']['planning']['yearly_hours']; ?></td>
                    <td class="currency">$<?php echo number_format($calc['personnel_savings']['planning']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>Modeling Trends</td>
                    <td class="number"><?php echo $calc['personnel_savings']['modeling_trends']['weekly_hours']; ?></td>
                    <td class="number"><?php echo $calc['personnel_savings']['modeling_trends']['yearly_hours']; ?></td>
                    <td class="currency">$<?php echo number_format($calc['personnel_savings']['modeling_trends']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>Improved problem resolution</td>
                    <td class="number"><?php echo $calc['personnel_savings']['problem_resolution']['weekly_hours']; ?></td>
                    <td class="number"><?php echo $calc['personnel_savings']['problem_resolution']['yearly_hours']; ?></td>
                    <td class="currency">$<?php echo number_format($calc['personnel_savings']['problem_resolution']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>On-going cost of building new reports</td>
                    <td class="number"><?php echo $calc['personnel_savings']['new_reports']['weekly_hours']; ?></td>
                    <td class="number"><?php echo $calc['personnel_savings']['new_reports']['yearly_hours']; ?></td>
                    <td class="currency">$<?php echo number_format($calc['personnel_savings']['new_reports']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>Capacity Report Collection</td>
                    <td class="number"><?php echo $calc['personnel_savings']['capacity_collection']['weekly_hours']; ?></td>
                    <td class="number"><?php echo $calc['personnel_savings']['capacity_collection']['yearly_hours']; ?></td>
                    <td class="currency">$<?php echo number_format($calc['personnel_savings']['capacity_collection']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>Service Improvement</td>
                    <td class="number"><?php echo $calc['personnel_savings']['service_improvement']['weekly_hours']; ?></td>
                    <td class="number"><?php echo $calc['personnel_savings']['service_improvement']['yearly_hours']; ?></td>
                    <td class="currency">$<?php echo number_format($calc['personnel_savings']['service_improvement']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>Automation</td>
                    <td class="number"><?php echo $calc['personnel_savings']['automation']['weekly_hours']; ?></td>
                    <td class="number"><?php echo $calc['personnel_savings']['automation']['yearly_hours']; ?></td>
                    <td class="currency">$<?php echo number_format($calc['personnel_savings']['automation']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>Ticket Reduction</td>
                    <td class="number"><?php echo $calc['personnel_savings']['ticket_reduction']['weekly_hours']; ?></td>
                    <td class="number"><?php echo $calc['personnel_savings']['ticket_reduction']['yearly_hours']; ?></td>
                    <td class="currency">$<?php echo number_format($calc['personnel_savings']['ticket_reduction']['annual_savings'], 2); ?></td>
                </tr>
                <tr>
                    <td>Manage SRM</td>
                    <td class="number"><?php echo $calc['personnel_savings']['manage_srm']['weekly_hours']; ?></td>
                    <td class="number"><?php echo $calc['personnel_savings']['manage_srm']['yearly_hours']; ?></td>
                    <td class="currency">$<?php echo number_format($calc['personnel_savings']['manage_srm']['annual_savings'], 2); ?></td>
                </tr>
                
                <!-- Operational Efficiencies -->
                <tr class="section-header">
                    <td colspan="3">Operational Efficiencies</td>
                    <td>Annual Savings</td>
                </tr>
                <tr>
                    <td colspan="3">Outage Avoidance</td>
                    <td class="currency">$<?php echo number_format($calc['operational_efficiencies']['outage_avoidance'], 2); ?></td>
                </tr>
                <tr>
                    <td colspan="3">Accuracy of Data Modeling/Forecasting</td>
                    <td class="currency">$<?php echo number_format($calc['operational_efficiencies']['data_modeling_accuracy'], 2); ?></td>
                </tr>
                <tr>
                    <td colspan="3">Visability by Business Unit</td>
                    <td class="currency">????</td>
                </tr>
                <tr>
                    <td colspan="3">Proactive Capacity Planning/Trending</td>
                    <td class="currency">Improved Discounting</td>
                </tr>
                
                <!-- Product Replacement -->
                <tr class="section-header">
                    <td colspan="4">Product Replacement</td>
                </tr>
                <tr>
                    <td colspan="3">Annual Maintenance (Current SRM)</td>
                    <td class="currency">????</td>
                </tr>
                
                <!-- Final Results -->
                <tr class="final-results">
                    <td colspan="3"><strong>Annual Savings</strong></td>
                    <td class="currency"><strong>$<?php echo number_format($calc['totals']['total_annual_savings'], 2); ?></strong></td>
                </tr>
                <tr class="final-results">
                    <td colspan="3"><strong>VSI Annual Cost</strong></td>
                    <td class="currency"><strong>$<?php echo number_format($calc['totals']['vsi_annual_cost'], 2); ?></strong></td>
                </tr>
                <tr class="final-results">
                    <td colspan="3"><strong>Payback (months)</strong></td>
                    <td class="number"><strong><?php echo number_format($calc['totals']['payback_months'], 2); ?></strong></td>
                </tr>
                <tr class="final-results">
                    <td colspan="3"><strong>Annual ROI</strong></td>
                    <td class="number"><strong><?php echo number_format($calc['totals']['annual_roi'], 0); ?>%</strong></td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    public function get_calculations() {
        return $this->calculations;
    }
    
    public function is_safe_range() {
        $roi = $this->calculations['totals']['annual_roi'];
        return ($roi >= 50 && $roi <= 1000);
    }
}
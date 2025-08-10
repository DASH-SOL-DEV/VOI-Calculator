<?php
class VOI_Calculator_PDF_Generator {

    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function generate() {
        // --- Calculations based on the sample ROI PDF ---
        $total_tb = $this->data['total_tb'];

        // Assumptions
        $cost_per_tb = 500;
        $employee_yearly_cost = 150000;
        $work_hours_yearly = 1880;
        $employee_hourly_rate = 80;

        // Cost Avoidance
        $reuse_space_savings = 0.02 * $total_tb * $cost_per_tb;
        $improved_processes_savings = 0.02 * $total_tb * $cost_per_tb;
        $improve_buying_accuracy_savings = 0.01 * $total_tb * $cost_per_tb;

        // Personnel Savings (Hrs/Weekly * 52 weeks * Hourly Rate)
        $time_building_reports_savings = 4 * 52 * $employee_hourly_rate;
        $time_planning_savings = 2 * 52 * $employee_hourly_rate;
        $modeling_trends_savings = 2 * 52 * $employee_hourly_rate;
        $improved_problem_resolution_savings = 4 * 52 * $employee_hourly_rate;
        $capacity_report_collection_savings = 4 * 52 * $employee_hourly_rate;
        $service_improvement_savings = 6 * 52 * $employee_hourly_rate;
        $automation_savings = 4 * 52 * $employee_hourly_rate;

        // Operational Efficiencies
        $outage_avoidance_savings = 250000; // Static value from sample

        // Totals
        $total_savings = $reuse_space_savings + $improved_processes_savings + $improve_buying_accuracy_savings +
                         $time_building_reports_savings + $time_planning_savings + $modeling_trends_savings +
                         $improved_problem_resolution_savings + $capacity_report_collection_savings +
                         $service_improvement_savings + $automation_savings + $outage_avoidance_savings;

        $vsi_annual_cost = 150000; // Static value from sample
        $payback_months = ($total_savings > 0) ? ($vsi_annual_cost / $total_savings) * 12 : 0;
        $annual_roi = ($vsi_annual_cost > 0) ? ($total_savings / $vsi_annual_cost) * 100 : 0;
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nifty Fifty Solution');
        $pdf->SetTitle('Visual Storage Intelligence ROI Worksheet');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // --- PDF Content ---
        // Using the HTML structure provided by the user
        $html = '
        <style>
            body { font-family: sans-serif; color: #000; }
            h1 { font-family: ff1; font-size: 16px; font-weight: normal; }
            h2 { font-family: ff1; font-size: 14px; font-weight: bold; margin-top: 15px; border-bottom: 1px solid #ccc; padding-bottom: 5px;}
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .section-title { font-size: 14px; font-weight: bold; }
            .value-col { text-align: right; }
            .header-info { margin-bottom: 20px; }
        </style>
        <body>
            <div class="header-info">
                <h1>Visual Storage Intelligence ROI Worksheet</h1>
                <p>Prepared for: ' . esc_html($this->data['company_name']) . '</p>
                <p>Date: ' . date('m/d/Y') . '</p>
            </div>

            <h2>Assumptions</h2>
            <table>
                <tr><th width="70%">Current Environment:</th><th width="30%" class="value-col"></th></tr>
                <tr><td>Total Space (TB)</td><td class="value-col">' . number_format($total_tb) . '</td></tr>
                <tr><td>Cost per TB</td><td class="value-col">$' . number_format($cost_per_tb, 2) . '</td></tr>
                <tr><th width="70%">Employee Cost:</th><th width="30%" class="value-col"></th></tr>
                <tr><td>Fully burdened yearly cost</td><td class="value-col">$' . number_format($employee_yearly_cost, 2) . '</td></tr>
                <tr><td>Work hours yearly</td><td class="value-col">' . number_format($work_hours_yearly) . '</td></tr>
                <tr><td>Hourly rate</td><td class="value-col">$' . number_format($employee_hourly_rate, 2) . '</td></tr>
            </table>

            <h2>Cost Avoidance</h2>
            <table>
                <tr><th width="40%"></th><th width="20%" class="value-col">% of Total Space</th><th width="20%" class="value-col">Space Savings (TB)</th><th width="20%" class="value-col">Annual Savings</th></tr>
                <tr><td>Reuse of Orphaned Space</td><td class="value-col">2%</td><td class="value-col">' . number_format(0.02 * $total_tb) . '</td><td class="value-col">$' . number_format($reuse_space_savings, 2) . '</td></tr>
                <tr><td>Improved Processes</td><td class="value-col">2.0%</td><td class="value-col">' . number_format(0.02 * $total_tb) . '</td><td class="value-col">$' . number_format($improved_processes_savings, 2) . '</td></tr>
                <tr><td>Improve Buying Accuracy</td><td class="value-col">1.0%</td><td class="value-col">' . number_format(0.01 * $total_tb) . '</td><td class="value-col">$' . number_format($improve_buying_accuracy_savings, 2) . '</td></tr>
            </table>
            
            <h2>Personnel Savings</h2>
            <table>
                <tr><th width="40%"></th><th width="20%" class="value-col">Hrs/Weekly</th><th width="20%" class="value-col">Hrs/Yearly</th><th width="20%" class="value-col">Annual Savings</th></tr>
                <tr><td>Time spent building reports</td><td class="value-col">4</td><td class="value-col">208</td><td class="value-col">$' . number_format($time_building_reports_savings, 2) . '</td></tr>
                <tr><td>Time Spent planning</td><td class="value-col">2</td><td class="value-col">104</td><td class="value-col">$' . number_format($time_planning_savings, 2) . '</td></tr>
                <tr><td>Modeling Trends</td><td class="value-col">2</td><td class="value-col">104</td><td class="value-col">$' . number_format($modeling_trends_savings, 2) . '</td></tr>
                <tr><td>Improved problem resolution</td><td class="value-col">4</td><td class="value-col">208</td><td class="value-col">$' . number_format($improved_problem_resolution_savings, 2) . '</td></tr>
                <tr><td>Capacity Report Collection</td><td class="value-col">4</td><td class="value-col">208</td><td class="value-col">$' . number_format($capacity_report_collection_savings, 2) . '</td></tr>
                <tr><td>Service Improvement</td><td class="value-col">6</td><td class="value-col">312</td><td class="value-col">$' . number_format($service_improvement_savings, 2) . '</td></tr>
                <tr><td>Automation</td><td class="value-col">4</td><td class="value-col">208</td><td class="value-col">$' . number_format($automation_savings, 2) . '</td></tr>
            </table>

            <h2>Operational Efficiencies</h2>
            <table>
                <tr><th width="70%"></th><th width="30%" class="value-col">Annual Savings</th></tr>
                <tr><td>Outage Avoidance</td><td class="value-col">$' . number_format($outage_avoidance_savings, 2) . '</td></tr>
            </table>

            <h2>Summary</h2>
            <table>
                <tr><td width="70%"><strong>Annual Savings</strong></td><td width="30%" class="value-col"><strong>$' . number_format($total_savings, 2) . '</strong></td></tr>
                <tr><td>VSI Annual Cost</td><td class="value-col">$' . number_format($vsi_annual_cost, 2) . '</td></tr>
                <tr><td>Payback (months)</td><td class="value-col">' . number_format($payback_months, 2) . '</td></tr>
                <tr><td><strong>Annual ROI</strong></td><td class="value-col"><strong>' . number_format($annual_roi, 0) . '%</strong></td></tr>
            </table>
        </body>';

        $pdf->writeHTML($html, true, false, true, false, '');
        // --- End PDF Content ---

        // Define file path
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/voi-calculator-pdfs/';
        $pdf_url_dir = $upload_dir['baseurl'] . '/voi-calculator-pdfs/';
        
        $filename = 'VSI-ROI-' . sanitize_title($this->data['company_name']) . '-' . time() . '.pdf';
        $filepath = $pdf_dir . $filename;
        $fileurl = $pdf_url_dir . $filename;

        try {
            // Close and output PDF document
            $pdf->Output($filepath, 'F');
            return ['path' => $filepath, 'url' => $fileurl];
        } catch (Exception $e) {
            return new WP_Error('pdf_generation_failed', $e->getMessage());
        }
    }
}

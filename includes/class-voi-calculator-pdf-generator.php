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
        $employee_hourly_rate = 80; // This is for display, the calculation below is more precise.
        
        // Corrected Calculation: Use the effective hourly rate based on burdened cost
        $effective_hourly_rate = $employee_yearly_cost / $work_hours_yearly;

        // Cost Avoidance
        $reuse_space_savings = 0.02 * $total_tb * $cost_per_tb;
        $improved_processes_savings = 0.02 * $total_tb * $cost_per_tb;
        $improve_buying_accuracy_savings = 0.01 * $total_tb * $cost_per_tb;

        // Personnel Savings (Hrs/Weekly * 52 weeks * Effective Hourly Rate)
        $time_building_reports_savings = 4 * 52 * $effective_hourly_rate;
        $time_planning_savings = 2 * 52 * $effective_hourly_rate;
        $modeling_trends_savings = 2 * 52 * $effective_hourly_rate;
        $improved_problem_resolution_savings = 4 * 52 * $effective_hourly_rate;
        $capacity_report_collection_savings = 4 * 52 * $effective_hourly_rate;
        $service_improvement_savings = 6 * 52 * $effective_hourly_rate;
        $automation_savings = 4 * 52 * $effective_hourly_rate;

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
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 10);

        // Add a page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 9);

        // --- PDF Content ---
        // Rebuilt HTML to closely match the provided PDF sample
        $html = '
        <style>
            body { font-family: sans-serif; color: #000; }
            .header-info { text-align: center; margin-bottom: 20px; }
            .header-info h1 { font-size: 14px; font-weight: bold; }
            .header-info p { font-size: 10px; }
            .section-title { font-size: 10px; font-weight: bold; background-color: #002060; color: #FFFFFF; padding: 5px; text-align: left; }
            .subsection-title { font-size: 10px; font-weight: bold; background-color: #BDD7EE; padding: 5px; text-align: left; }
            .summary-section td { background-color: #F2F2F2; }
            .summary-total td { background-color: #BDD7EE; font-weight: bold; }
            table { width: 100%; border-collapse: collapse; margin-top: 0; margin-bottom: 10px; }
            th, td { border: 1px solid #000000; padding: 8px; }
            th { font-weight: bold; text-align: center; background-color: #BDD7EE; }
            .value-col { text-align: right; }
            .label-col { text-align: left; }
            .dollar-sign { text-align: center; }
        </style>
        <body>
            <div class="header-info">
                <h1>Visual Storage Intelligence ROI Worksheet</h1>
                <p>Prepared for: ' . esc_html($this->data['company_name']) . ' (Estimated Value)</p>
                <p>' . date('m/d/Y') . '</p>
            </div>

            <table>
                <tr><td colspan="3" class="section-title">Assumptions</td></tr>
                <tr><td colspan="3" class="subsection-title">Current Environment:</td></tr>
                <tr><td width="60%" class="label-col">Total Space (TB)</td><td width="10%" class="dollar-sign"></td><td width="30%" class="value-col">' . number_format($total_tb) . '</td></tr>
                <tr><td class="label-col">Cost per TB</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($cost_per_tb, 2) . '</td></tr>
                <tr><td colspan="3" class="subsection-title">Employee Cost:</td></tr>
                <tr><td class="label-col">Fully burdened yearly cost</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($employee_yearly_cost, 2) . '</td></tr>
                <tr><td class="label-col">Work hours yearly</td><td class="dollar-sign"></td><td class="value-col">' . number_format($work_hours_yearly) . '</td></tr>
                <tr><td class="label-col">Hourly rate</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($employee_hourly_rate, 2) . '</td></tr>
            </table>

            <table>
                <thead>
                    <tr><th width="40%" class="section-title label-col">Cost Avoidance</th><th width="15%" class="section-title">% of Total Space</th><th width="15%" class="section-title">Space Savings (TB)</th><th width="10%" class="section-title"></th><th width="20%" class="section-title">Annual Savings</th></tr>
                </thead>
                <tbody>
                    <tr><td class="label-col">Reuse of Orphaned Space</td><td class="value-col">2.0%</td><td class="value-col">' . number_format(0.02 * $total_tb) . '</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($reuse_space_savings, 2) . '</td></tr>
                    <tr><td class="label-col">Improved Processes</td><td class="value-col">2.0%</td><td class="value-col">' . number_format(0.02 * $total_tb) . '</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($improved_processes_savings, 2) . '</td></tr>
                    <tr><td class="label-col">Improve Buying Accuracy</td><td class="value-col">1.0%</td><td class="value-col">' . number_format(0.01 * $total_tb) . '</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($improve_buying_accuracy_savings, 2) . '</td></tr>
                </tbody>
            </table>
            
            <table>
                <thead>
                    <tr><th width="40%" class="section-title label-col">Personnel Savings</th><th width="15%" class="section-title">Hrs/Weekly</th><th width="15%" class="section-title">Hrs/Yearly</th><th width="10%" class="section-title"></th><th width="20%" class="section-title">Annual Savings</th></tr>
                </thead>
                <tbody>
                    <tr><td class="label-col">Time spent building reports</td><td class="value-col">4</td><td class="value-col">208</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($time_building_reports_savings, 2) . '</td></tr>
                    <tr><td class="label-col">Time Spent planning</td><td class="value-col">2</td><td class="value-col">104</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($time_planning_savings, 2) . '</td></tr>
                    <tr><td class="label-col">Modeling Trends</td><td class="value-col">2</td><td class="value-col">104</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($modeling_trends_savings, 2) . '</td></tr>
                    <tr><td class="label-col">Improved problem resolution</td><td class="value-col">4</td><td class="value-col">208</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($improved_problem_resolution_savings, 2) . '</td></tr>
                    <tr><td class="label-col">Capacity Report Collection</td><td class="value-col">4</td><td class="value-col">208</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($capacity_report_collection_savings, 2) . '</td></tr>
                    <tr><td class="label-col">Service Improvement</td><td class="value-col">6</td><td class="value-col">312</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($service_improvement_savings, 2) . '</td></tr>
                    <tr><td class="label-col">Automation</td><td class="value-col">4</td><td class="value-col">208</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($automation_savings, 2) . '</td></tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr><th width="70%" class="section-title label-col">Operational Efficiencies</th><th width="10%" class="section-title"></th><th width="20%" class="section-title">Annual Savings</th></tr>
                </thead>
                <tbody>
                    <tr><td class="label-col">Outage Avoidance</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($outage_avoidance_savings, 2) . '</td></tr>
                </tbody>
            </table>

            <br/><br/>

            <table class="summary-section">
                <tr class="summary-total"><td width="70%" class="label-col">Annual Savings</td><td width="10%" class="dollar-sign">$</td><td width="20%" class="value-col">' . number_format($total_savings, 2) . '</td></tr>
                <tr><td class="label-col">VSI Annual Cost</td><td class="dollar-sign">$</td><td class="value-col">' . number_format($vsi_annual_cost, 2) . '</td></tr>
                <tr><td class="label-col">Payback (months)</td><td class="dollar-sign"></td><td class="value-col">' . number_format($payback_months, 2) . '</td></tr>
                <tr class="summary-total"><td class="label-col">Annual ROI</td><td class="dollar-sign"></td><td class="value-col">' . number_format($annual_roi, 0) . '%</td></tr>
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
            return ['path' => $filepath, 'url' => $fileurl, 'html' => $html];
        } catch (Exception $e) {
            return new WP_Error('pdf_generation_failed', $e->getMessage());
        }
    }
}

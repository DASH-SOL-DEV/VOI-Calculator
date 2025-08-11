<div id="voi-calculator-container" class="voi-calculator-container">
    <div class="voi-form-wrapper" <?php if ($show_results) echo 'style="display:none;"'; ?>>
        <div class="voi-header">
            <h2>Simplified Value Calculator</h2>
            <p>This calculator uses industry standard values to show the value of Visual One Intelligence.</p>
        </div>
        <form id="voi-calculator-form" class="voi-calculator-form" method="post">
            <div id="voi-form-message" class="voi-form-message" style="display:none;"></div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="total_tb">Enter Total Sold TB of storage</label>
                    <input type="number" id="total_tb" name="total_tb" placeholder="e.g., 1000" required>
                </div>
                <div class="form-group">
                    <label for="total_vms">Enter Total Number of VMs</label>
                    <input type="number" id="total_vms" name="total_vms" placeholder="e.g., 300" required>
                </div>
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" placeholder="e.g., ACME Widget Company" required>
                </div>
                <div class="form-group">
                    <label for="company_url">Company URL</label>
                    <input type="url" id="company_url" name="company_url" placeholder="e.g., company.com" required>
                </div>
                <div class="form-group">
                    <label for="full_name">First Name / Last Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="e.g., Joe Public" required>
                </div>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" placeholder="e.g., sample.name@emailaddress.com" required>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="voi-submit-button">Generate Value Document and See Results</button>
            </div>
        </form>
    </div>
    <div id="voi-calculator-results" <?php if (!$show_results) echo 'style="display:none;"'; ?>>
        <?php if ($show_results) : ?>
            <?php echo $results_data['roi_html']; ?>
            <div class="results-actions">
                <a href="<?php echo esc_url($results_data['pdf_url']); ?>" target="_blank" class="voi-download-button">Download PDF</a>
                <button type="button" class="voi-calculate-again-button">Calculate Again</button>
            </div>
        <?php endif; ?>
    </div>
</div>

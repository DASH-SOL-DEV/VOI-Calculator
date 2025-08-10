<?php
/**
 * VOI Calculator HubSpot Integration
 * Handles contact creation and lead management in HubSpot
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class VOI_HubSpot_Integration {
    
    private $api_key;
    private $base_url = 'https://api.hubapi.com';
    
    public function __construct() {
        $this->api_key = get_option('voi_hubspot_api_key', '');
    }
    
    /**
     * Send form submission to HubSpot
     */
    public function create_contact($submission_data, $calculations = null) {
        if (empty($this->api_key)) {
            error_log('VOI Calculator: HubSpot API key not configured');
            return false;
        }
        
        // Prepare contact data
        $contact_data = $this->prepare_contact_data($submission_data, $calculations);
        
        // First, try to find existing contact
        $existing_contact = $this->find_contact_by_email($submission_data['email']);
        
        if ($existing_contact) {
            // Update existing contact
            return $this->update_contact($existing_contact['id'], $contact_data);
        } else {
            // Create new contact
            return $this->create_new_contact($contact_data);
        }
    }
    
    /**
     * Prepare contact data for HubSpot
     */
    private function prepare_contact_data($submission_data, $calculations) {
        $contact_properties = [
            'email' => $submission_data['email'],
            'firstname' => $submission_data['first_name'],
            'lastname' => $submission_data['last_name'],
            'company' => $submission_data['company_name'],
            'website' => $submission_data['company_url'],
            'phone' => '', // Not collected in current form
            'jobtitle' => '', // Not collected in current form
            'lifecyclestage' => 'lead',
            'lead_source' => 'VOI Calculator'
        ];
        
        // Add custom properties for VOI Calculator data
        $custom_properties = [
            'voi_storage_tb' => $submission_data['storage_tb'],
            'voi_vm_count' => $submission_data['vm_count'],
            'voi_submission_date' => date('Y-m-d H:i:s')
        ];
        
        // Add calculated ROI data if available
        if ($calculations) {
            $custom_properties['voi_annual_savings'] = $calculations['totals']['total_annual_savings'];
            $custom_properties['voi_annual_cost'] = $calculations['totals']['vsi_annual_cost'];
            $custom_properties['voi_payback_months'] = $calculations['totals']['payback_months'];
            $custom_properties['voi_annual_roi'] = $calculations['totals']['annual_roi'];
            $custom_properties['voi_safe_range'] = ($calculations['totals']['annual_roi'] >= 50 && $calculations['totals']['annual_roi'] <= 1000) ? 'Yes' : 'No';
        }
        
        return array_merge($contact_properties, $custom_properties);
    }
    
    /**
     * Find existing contact by email
     */
    private function find_contact_by_email($email) {
        $url = $this->base_url . '/crm/v3/objects/contacts/search';
        
        $search_data = [
            'filterGroups' => [
                [
                    'filters' => [
                        [
                            'propertyName' => 'email',
                            'operator' => 'EQ',
                            'value' => $email
                        ]
                    ]
                ]
            ],
            'properties' => ['id', 'email', 'firstname', 'lastname']
        ];
        
        $response = $this->make_api_request('POST', $url, $search_data);
        
        if ($response && isset($response['results']) && count($response['results']) > 0) {
            return $response['results'][0];
        }
        
        return false;
    }
    
    /**
     * Create new contact in HubSpot
     */
    private function create_new_contact($contact_data) {
        $url = $this->base_url . '/crm/v3/objects/contacts';
        
        $data = [
            'properties' => $contact_data
        ];
        
        $response = $this->make_api_request('POST', $url, $data);
        
        if ($response && isset($response['id'])) {
            // Contact created successfully
            $this->create_note($response['id'], 'VOI Calculator submission received');
            return $response;
        }
        
        return false;
    }
    
    /**
     * Update existing contact in HubSpot
     */
    private function update_contact($contact_id, $contact_data) {
        $url = $this->base_url . '/crm/v3/objects/contacts/' . $contact_id;
        
        $data = [
            'properties' => $contact_data
        ];
        
        $response = $this->make_api_request('PATCH', $url, $data);
        
        if ($response && isset($response['id'])) {
            // Contact updated successfully
            $this->create_note($contact_id, 'VOI Calculator submission updated - new ROI calculation');
            return $response;
        }
        
        return false;
    }
    
    /**
     * Create a note/activity on the contact
     */
    private function create_note($contact_id, $note_content) {
        $url = $this->base_url . '/crm/v3/objects/notes';
        
        $data = [
            'properties' => [
                'hs_note_body' => $note_content,
                'hs_timestamp' => (time() * 1000) // HubSpot expects milliseconds
            ],
            'associations' => [
                [
                    'to' => [
                        'id' => $contact_id
                    ],
                    'types' => [
                        [
                            'associationCategory' => 'HUBSPOT_DEFINED',
                            'associationTypeId' => 202 // Note to Contact association
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->make_api_request('POST', $url, $data);
    }
    
    /**
     * Create or update a deal for high-value prospects
     */
    public function create_deal($contact_id, $submission_data, $calculations) {
        if (!$calculations || $calculations['totals']['annual_roi'] < 200) {
            return false; // Only create deals for high ROI prospects
        }
        
        $url = $this->base_url . '/crm/v3/objects/deals';
        
        $deal_amount = $calculations['totals']['vsi_annual_cost'];
        $deal_name = $submission_data['company_name'] . ' - VOI Calculator Lead';
        
        $data = [
            'properties' => [
                'dealname' => $deal_name,
                'amount' => $deal_amount,
                'pipeline' => 'default',
                'dealstage' => 'appointmentscheduled', // Adjust based on your pipeline
                'closedate' => date('Y-m-d', strtotime('+90 days')),
                'deal_source' => 'VOI Calculator',
                'description' => "Lead from VOI Calculator\nStorage: {$submission_data['storage_tb']} TB\nVMs: {$submission_data['vm_count']}\nROI: {$calculations['totals']['annual_roi']}%"
            ],
            'associations' => [
                [
                    'to' => [
                        'id' => $contact_id
                    ],
                    'types' => [
                        [
                            'associationCategory' => 'HUBSPOT_DEFINED',
                            'associationTypeId' => 3 // Deal to Contact association
                        ]
                    ]
                ]
            ]
        ];
        
        return $this->make_api_request('POST', $url, $data);
    }
    
    /**
     * Send notification email to sales team
     */
    public function notify_sales_team($submission_data, $calculations, $is_safe_range) {
        // This would typically use HubSpot workflows or sequences
        // For now, we'll create a task for the sales team
        
        $url = $this->base_url . '/crm/v3/objects/tasks';
        
        $urgency = $is_safe_range ? 'Normal' : 'High';
        $roi = $calculations ? $calculations['totals']['annual_roi'] : 'N/A';
        
        $task_subject = $is_safe_range ? 
            "New VOI Calculator Lead: {$submission_data['company_name']}" : 
            "URGENT - VOI Calculator Lead (Outside Safe Range): {$submission_data['company_name']}";
            
        $task_body = "New VOI Calculator submission received:\n\n";
        $task_body .= "Company: {$submission_data['company_name']}\n";
        $task_body .= "Contact: {$submission_data['first_name']} {$submission_data['last_name']}\n";
        $task_body .= "Email: {$submission_data['email']}\n";
        $task_body .= "Website: {$submission_data['company_url']}\n";
        $task_body .= "Storage: {$submission_data['storage_tb']} TB\n";
        $task_body .= "VMs: {$submission_data['vm_count']}\n";
        
        if ($calculations) {
            $task_body .= "\nCalculated ROI: {$roi}%\n";
            $task_body .= "Annual Savings: $" . number_format($calculations['totals']['total_annual_savings']) . "\n";
            $task_body .= "Payback: " . number_format($calculations['totals']['payback_months'], 1) . " months\n";
            
            if (!$is_safe_range) {
                $task_body .= "\n⚠️ WARNING: ROI outside safe range - requires manual review\n";
            }
        }
        
        $data = [
            'properties' => [
                'hs_task_subject' => $task_subject,
                'hs_task_body' => $task_body,
                'hs_task_status' => 'NOT_STARTED',
                'hs_task_priority' => $urgency === 'High' ? 'HIGH' : 'MEDIUM',
                'hs_timestamp' => (time() * 1000),
                'hs_task_type' => 'CALL'
            ]
        ];
        
        return $this->make_api_request('POST', $url, $data);
    }
    
    /**
     * Make API request to HubSpot
     */
    private function make_api_request($method, $url, $data = null) {
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json'
        ];
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            error_log("VOI Calculator HubSpot API Error: " . $error);
            return false;
        }
        
        if ($http_code >= 400) {
            error_log("VOI Calculator HubSpot API HTTP Error: " . $http_code . " - " . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return ['success' => false, 'message' => 'API key not configured'];
        }
        
        $url = $this->base_url . '/crm/v3/objects/contacts?limit=1';
        $response = $this->make_api_request('GET', $url);
        
        if ($response !== false) {
            return ['success' => true, 'message' => 'Connection successful'];
        } else {
            return ['success' => false, 'message' => 'Connection failed'];
        }
    }
    
    /**
     * Set API key
     */
    public function set_api_key($api_key) {
        $this->api_key = $api_key;
        update_option('voi_hubspot_api_key', $api_key);
    }
    
    /**
     * Get API key
     */
    public function get_api_key() {
        return $this->api_key;
    }
}
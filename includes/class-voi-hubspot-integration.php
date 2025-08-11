<?php
/**
 * HubSpot Integration Class for VOI Calculator
 * 
 * Save this as: includes/class-voi-hubspot-integration.php
 */

class VOI_HubSpot_Integration {
    
    private $api_key;
    private $portal_id;
    
    public function __construct() {
        // Get HubSpot settings from WordPress options
        $this->api_key = get_option('voi_hubspot_api_key', '');
        $this->portal_id = get_option('voi_hubspot_portal_id', '');
    }
    
    /**
     * Main method to send contact data to HubSpot
     * 
     * @param array $form_data Form submission data
     * @param array $calculations Optional ROI calculations
     * @return array|WP_Error HubSpot response or error
     */
    public function create_or_update_contact($form_data, $calculations = []) {
        if (empty($this->api_key)) {
            return new WP_Error('hubspot_error', 'HubSpot API key not configured');
        }
        
        // Prepare data for HubSpot
        $hubspot_data = $this->prepare_hubspot_data($form_data, $calculations);
        
        // Try to create contact first
        $result = $this->create_contact($hubspot_data);
        
        // If contact exists (409 error), try to update instead
        if (is_wp_error($result) && $this->is_conflict_error($result)) {
            $result = $this->update_existing_contact($hubspot_data);
        }
        
        return $result;
    }
    
    /**
     * Prepare form data for HubSpot format
     * 
     * @param array $form_data
     * @param array $calculations (not used, kept for compatibility)
     * @return array
     */
    private function prepare_hubspot_data($form_data, $calculations = []) {
        // Start with minimal data first
        $hubspot_data = [
            'properties' => [
                // Standard HubSpot properties only
                'email' => $form_data['email'],
                'firstname' => $this->extract_first_name($form_data['full_name']),
                'lastname' => $this->extract_last_name($form_data['full_name']),
                'company' => $form_data['company_name'],
                'website' => $this->clean_url($form_data['company_url']),
                'lifecyclestage' => 'lead'
            ]
        ];
        
        // Add custom properties only if they're likely to exist
        // Comment these out if you haven't created custom properties yet
        /*
        $hubspot_data['properties']['voi_total_tb'] = strval($form_data['total_tb']);
        $hubspot_data['properties']['voi_total_vms'] = strval($form_data['total_vms']);
        $hubspot_data['properties']['voi_submission_date'] = date('Y-m-d');
        $hubspot_data['properties']['lead_source'] = 'VOI Calculator';
        */
        
        return $hubspot_data;
    }
    
    /**
     * Create new contact in HubSpot
     * 
     * @param array $data
     * @return array|WP_Error
     */
    private function create_contact($data) {
        $url = 'https://api.hubapi.com/crm/v3/objects/contacts';
        
        $response = $this->make_hubspot_request('POST', $url, $data);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return json_decode($response_body, true);
        }
        
        return new WP_Error('hubspot_create_error', 'Failed to create contact: ' . $response_body, ['code' => $response_code]);
    }
    
    /**
     * Update existing contact in HubSpot
     * 
     * @param array $data
     * @return array|WP_Error
     */
    private function update_existing_contact($data) {
        // First, find the contact by email
        $contact_id = $this->find_contact_by_email($data['properties']['email']);
        
        if (is_wp_error($contact_id)) {
            return $contact_id;
        }
        
        if (!$contact_id) {
            return new WP_Error('hubspot_error', 'Contact not found for update');
        }
        
        // Update the contact
        $url = 'https://api.hubapi.com/crm/v3/objects/contacts/' . $contact_id;
        
        $response = $this->make_hubspot_request('PATCH', $url, $data);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return json_decode(wp_remote_retrieve_body($response), true);
        }
        
        return new WP_Error('hubspot_update_error', 'Failed to update contact: ' . wp_remote_retrieve_body($response));
    }
    
    /**
     * Find contact by email address
     * 
     * @param string $email
     * @return string|WP_Error|null Contact ID or error
     */
    private function find_contact_by_email($email) {
        $url = 'https://api.hubapi.com/crm/v3/objects/contacts/search';
        
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
            'properties' => ['id', 'email']
        ];
        
        $response = $this->make_hubspot_request('POST', $url, $search_data);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($response_body['results'])) {
            return $response_body['results'][0]['id'];
        }
        
        return null;
    }
    
    /**
     * Make HTTP request to HubSpot API
     * 
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array|WP_Error
     */
    private function make_hubspot_request($method, $url, $data = []) {
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ];
        
        if (!empty($data)) {
            $json_data = json_encode($data);
            $args['body'] = $json_data;
            
            // DEBUG: Log the request details
            error_log('VOI DEBUG - HubSpot Request URL: ' . $url);
            error_log('VOI DEBUG - HubSpot Request Method: ' . $method);
            error_log('VOI DEBUG - HubSpot Request Data: ' . $json_data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return new WP_Error('hubspot_request_error', 'HTTP request failed: ' . $response->get_error_message());
        }
        
        // DEBUG: Log the response
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        error_log('VOI DEBUG - HubSpot Response Code: ' . $response_code);
        error_log('VOI DEBUG - HubSpot Response Body: ' . $response_body);
        
        return $response;
    }
    
    /**
     * Check if error is a conflict (contact already exists)
     * 
     * @param WP_Error $error
     * @return bool
     */
    private function is_conflict_error($error) {
        $error_data = $error->get_error_data();
        return isset($error_data['code']) && $error_data['code'] == 409;
    }
    
    /**
     * Extract first name from full name
     * 
     * @param string $full_name
     * @return string
     */
    private function extract_first_name($full_name) {
        $parts = explode(' ', trim($full_name));
        return isset($parts[0]) ? $parts[0] : '';
    }
    
    /**
     * Extract last name from full name
     * 
     * @param string $full_name
     * @return string
     */
    private function extract_last_name($full_name) {
        $parts = explode(' ', trim($full_name));
        if (count($parts) > 1) {
            array_shift($parts); // Remove first name
            return implode(' ', $parts);
        }
        return '';
    }
    
    /**
     * Clean and validate URL
     * 
     * @param string $url
     * @return string
     */
    private function clean_url($url) {
        // Add http:// if no protocol specified
        if (!empty($url) && !preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        return $url;
    }
    
    /**
     * Test HubSpot connection
     * 
     * @return bool|WP_Error
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('hubspot_error', 'API key not configured');
        }
        
        $url = 'https://api.hubapi.com/crm/v3/objects/contacts?limit=1';
        
        $response = $this->make_hubspot_request('GET', $url);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return true;
        }
        
        return new WP_Error('hubspot_connection_error', 'Connection test failed: ' . wp_remote_retrieve_body($response));
    }
    
    /**
     * Get required custom properties for HubSpot
     * 
     * @return array
     */
    public static function get_required_custom_properties() {
        return [
            'voi_total_tb' => [
                'name' => 'voi_total_tb',
                'label' => 'VOI Total TB',
                'type' => 'string',
                'fieldType' => 'text'
            ],
            'voi_total_vms' => [
                'name' => 'voi_total_vms',
                'label' => 'VOI Total VMs',
                'type' => 'string',
                'fieldType' => 'text'
            ],
            'voi_submission_date' => [
                'name' => 'voi_submission_date',
                'label' => 'VOI Submission Date',
                'type' => 'date',
                'fieldType' => 'date'
            ]
        ];
    }
}
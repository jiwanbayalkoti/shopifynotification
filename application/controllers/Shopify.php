<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shopify extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }
    
    public function inventory_details() {
        //get inventory data from post request
        $inventoryData = file_get_contents('php://input');
        $inventoryData = json_decode($inventoryData, true);

        $inventory_item_id = $inventoryData['inventory_item_id'];
      
        $shop_url = 'https://gyrovi-test.myshopify.com';
        $rest_url = $shop_url . '/admin/api/2025-07/inventory_items/' . $inventory_item_id . '.json';
        
        $access_token = 'YOUR_SHOPIFY_ACCESS_TOKEN_HERE'; // Replace with actual token
        
        $headers = [
            'X-Shopify-Access-Token: ' . $access_token,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $response = $this->make_rest_request($rest_url, $headers);
        
        if ($response) {
            
            $this->get_product_variants($response['inventory_item']['sku']);
        } else {
            echo "Failed to fetch inventory item from Shopify";
        }
    }

  

    private function make_rest_request($url, $headers) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        if ($http_code == 200) {
            return json_decode($response, true);
        } else {
            echo "HTTP Error: " . $http_code;
            echo "Response: " . $response;
            return false;
        }
    }

    public function get_product_variants($sku) {
        $shop_url = 'https://gyrovi-test.myshopify.com';
        $graphql_url = $shop_url . '/admin/api/2025-07/graphql.json';
        
        $access_token = 'YOUR_SHOPIFY_ACCESS_TOKEN_HERE'; // Replace with actual token
        
        // GraphQL query to get product variants by SKU
        $query = '
        query ProductVariantsList {
          productVariants(first: 10, query: "sku:'.$sku.'") {
            nodes {
              id
              title
              product {
                title
              }
              sku
              price
              inventoryQuantity
            }
            pageInfo {
              startCursor
              endCursor
            }
          }
        }';
        
        $headers = [
            'X-Shopify-Access-Token: ' . $access_token,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $data = json_encode(['query' => $query]);
        
        $response = $this->make_graphql_request($graphql_url, $data, $headers);
        
        if ($response) {
           
            $this->process_product_variants($response);
        } else {
            echo "Failed to fetch product variants from Shopify";
        }
    }
    private function make_graphql_request($url, $data, $headers) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            echo "cURL Error: " . curl_error($ch);
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        if ($http_code == 200) {
            return json_decode($response, true);
        } else {
            echo "HTTP Error: " . $http_code;
            echo "Response: " . $response;
            return false;
        }
    }

    private function process_product_variants($data) {
        if (!isset($data['data']['productVariants']['nodes'])) {
            echo "No product variants found";
            return;
        }
        
        $low_stock_items = [];
        
        foreach ($data['data']['productVariants']['nodes'] as $variant) {
            $sku = $variant['sku'] ?? 'N/A';
            $inventory_quantity = $variant['inventoryQuantity'] ?? 0;
            
            // Check if inventory is low
            if (in_array($inventory_quantity, [20, 10, 5]) || $inventory_quantity < 5) {
                $low_stock_items[] = [
                    'product_title' => $variant['product']['title'],
                    'variant_title' => $variant['title'],
                    'sku' => $sku,
                    'inventory_quantity' => $inventory_quantity,
                ];
            }
        }
        
        // Send email only if there are low stock items
        if (!empty($low_stock_items)) {
            echo "<h3>Found " . count($low_stock_items) . " low stock items:</h3>";
            echo "<ul>";
            foreach ($low_stock_items as $item) {
                echo "<li>" . $item['product_title'] . " (Stock: " . $item['inventory_quantity'] . ")</li>";
            }
            echo "</ul>";
            
            $recipients = [
                'navin.bista@gyrovi.com',
                'malaw.manandhar@gyrovi.com',
                'ranjit.anesh@gmail.com'
            ];
            $this->send_low_stock_bulk_email($low_stock_items, $recipients);
        } else {
            echo "<p style='color: blue;'>No low stock items found.</p>";
        }
    }

    private function send_low_stock_bulk_email($low_stock_items, $recipients = null) {
        $this->load->library('email');
        $this->email->clear();
        
        // Default recipients if none provided
        if ($recipients === null) {
            $recipients = ['flyjiwan1992@gmail.com'];
        }
        
        // If recipients is a string, convert to array
        if (is_string($recipients)) {
            $recipients = [$recipients];
        }
        
        $this->email->from('jiwanbayalkoti@gmail.com', 'Shopify Inventory Alert');
        
        // Add multiple recipients - use comma-separated string for multiple recipients
        $this->email->to(implode(',', $recipients));
        
        $this->email->subject('Low Stock Alert');
        
        $message = "The following products have low stock:\n\n";
        
        foreach ($low_stock_items as $index => $item) {
            $message .= "Product Name: " . $item['product_title'] . "\n";
            $message .= "Variant: " . $item['variant_title'] . "\n";
            $message .= "SKU: " . $item['sku'] . "\n";
            $message .= "Current Stock: " . $item['inventory_quantity'] . "\n";
            $message .= "-----------------------------------------------------------\n";
        }
        
        $this->email->message($message);
        
        $this->email->send();
           
    }

  
} 
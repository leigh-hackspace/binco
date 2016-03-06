<?php namespace BinCo\Scrapers;

use \GuzzleHttp\Client;

class ScrapeAddress {
    
    public function __construct($PostCode)
    {
        $this->postcode = $PostCode;
        $this->httpclient = new \GuzzleHttp\Client();
    }

    public function getAddresses()
    {
        $response = $this->httpclient->request('POST', 'http://kinnear.wigan.gov.uk/bincollections/Default.aspx', array(
            'form_params' => array(
                'txtPostCode' => $this->postcode,
                '__VIEWSTATE' => '/wEPDwUKLTc1NTAxMDQ4NQ9kFgICAw9kFgoCAw8PFgIeBFRleHQFBldONSA5U2RkAgcPDxYCHgdWaXNpYmxlaGRkAgkPDxYCHwFnZBYEAgEPFgIfAWhkAgMPEA8WBB4EUm93cwIKHwFoZBAVABUAFCsDABYAZAILDw8WAh8BaGRkAg0PDxYEHwAFME5vIEFkZHJlc3MgRm91bmQuIFBsZWFzZSBlbnRlciBhIG5ldyBzZWFyY2ggdGVybR8BZ2RkZGdJ/f1QUiT7YcV+TbWTVz54J5jQ',
                '__EVENTVALIDATION' => '/wEWBAKM4+TaCQL++IESAuGVvaUCAsvbsOcMOMcqkaXtIcOB4n7N3gkJG13axQo='
            )
        ));

        if($response->getStatusCode() == 200) {
            $Addresses = $this->parseResponseBody((string) $response->getBody());
            return $Addresses;
        } else {
            $TechInfo = "Status Code: {$response->getStatusCode()} {$response->getReasonPhrase()}\n";
            $Headers = print_r($response->getHeaders, true);
            $TechInfo .= "Headers:\n {$Headers}\n\n";
            $Body = (string) $response->getBody();
            $TechInfo .= "Body:\n {$Body}";
            throw new \Exception('We got a HTTP Error :( Technical Info: '.base64_encode($TechInfo));
        }
    }

    private function parseResponseBody($Response)
    {
        require_once 'simple_html_dom.php';
        $RootObj = str_get_html($Response);
        if($RootObj->find('select[id=lbAddresses]') == null) {
            $Error = $RootObj->find('form p.error span[id=lblError]', 0)->plaintext;
            throw new \Exception($Error);
        } else {
            $Addresses = array();
            $Addresses['EventValidation'] = $RootObj->find('form[id=Form1] input[id=__EVENTVALIDATION]', 0)->value;
            $Addresses['ViewState'] = $RootObj->find('form[id=Form1] input[id=__VIEWSTATE]', 0)->value;

            foreach($RootObj->find('select[id=lbAddresses] option') as $Address) {
                if(strpos($Address->value, 'UPRN') !== false) {
                    $Addresses[] = array(
                        'uprn' => $Address->value,
                        'address' => $Address->plaintext
                    );
                }
            }
            return $Addresses;
        }
    }

}

<?php
namespace Omnipay\NestPay\Message;

use DOMDocument;
use Omnipay\Common\Message\AbstractRequest;

/**
 * NestPay Purchase Request
 *
 * (c) Yasin Kuyu
 * 2015, insya.com
 * http://www.github.com/yasinkuyu/omnipay-nestpay
 */
class PurchaseRequest extends AbstractRequest
{

    protected $endpoint = '';

    public function getData()
    {
        $this->validate('amount', 'card');
        $this->getCard()->validate();
        
        $data['Email'] = $this->getCard()->getEmail();
        $data['OrderId'] = $this->getTransactionId();
        $data['GroupId'] = '';
        $data['TransId'] = '';
        $data['UserId'] = '';
        $data['Type'] = $this->getType();
        $data['Currency'] = $this->getCurrencyNumeric();
        $data['Installment'] = $this->getInstallment();
        
        $data['Total'] = $this->getAmount();
        $data['Number'] = $this->getCard()->getNumber();
        $data['Expires'] = $this->getCard()->getExpiryDate('my');
        $data["Cvv2Val"] = $this->getCard()->getCvv();
        $data["IPAddress"] = $this->getClientIp();
        
        return $data;
    }

    public function sendData($data)
    {
        
        // API info
        $data['Name'] = $this->getUserName();
        $data['ClientId'] = $this->getClientId();
        $data['Password'] = $this->getPassword();
        $data['Mode'] = $this->getTestMode() ? 'T' : 'P';
        
        // Get geteway
        // ex: isbank
        $gateway = $this->getBank();
        
        // Todo: http protocol
        $protocol = 'https://';
        
        if (!isset($this->endpoint)) {
            throw new \Exception('Invalid endpoint url');
        }
        
        $document = new DOMDocument('1.0', 'UTF-8');
        $root = $document->createElement('CC5Request');
        
        // Each array element
        foreach ($data as $id => $value) {
            $root->appendChild($document->createElement($id, $value));
        }
        
        $document->appendChild($root);
        
        if ($this->getCard() && ! empty($this->getCard()->getFirstName())) {
            $dataShip = [
                "Name" => $this->getCard()->getFirstName() . " " . $this->getCard()->getLastName(),
                "Street1" => $this->getCard()->getShippingAddress1(),
                "Street2" => $this->getCard()->getShippingAddress2(),
                "Street3" => "",
                "City" => $this->getCard()->getShippingCity(),
                "StateProv" => $this->getCard()->getShippingState(),
                "PostalCode" => $this->getCard()->getShippingPostcode(),
                "Country" => $this->getCard()->getShippingCountry(),
                "Company" => $this->getCard()->getCompany(),
                "TelVoice" => $this->getCard()->getShippingPhone()
            ];
            
            $shipTo = $document->createElement('ShipTo');
            foreach ($dataShip as $id => $value) {
                $shipTo->appendChild($document->createElement($id, $value));
            }
            $root->appendChild($shipTo);
            
            $dataBill = [
                "Name" => $this->getCard()->getFirstName() . " " . $this->getCard()->getLastName(),
                "Street1" => $this->getCard()->getBillingAddress1(),
                "Street2" => $this->getCard()->getBillingAddress2(),
                "Street3" => "",
                "City" => $this->getCard()->getBillingCity(),
                "StateProv" => $this->getCard()->getBillingState(),
                "PostalCode" => $this->getCard()->getBillingPostcode(),
                "Country" => $this->getCard()->getBillingCountry(),
                "Company" => $this->getCard()->getCompany(),
                "TelVoice" => $this->getCard()->getBillingPhone()
            ];
        }
        
        // Set money points (maxi puan)
        $extra = $document->createElement('Extra');
        if (! empty($this->getMoneyPoints())) {
            $extra->appendChild($document->createElement('MAXIPUAN', $this->getMoneyPoints()));
            $root->appendChild($extra);
        }
        
        // Get money points (maxi puan)
        if (! empty($this->getMoneyPoints())) {
            $extra->appendChild($document->createElement('MAXIPUANSORGU', 'MAXIPUANSORGU'));
            $root->appendChild($extra);
        }
        
        // Settlement
        if (! empty($this->getSettlement())) {
            $extra->appendChild($document->createElement('SETTLE', 'SETTLE'));
            $root->appendChild($extra);
        }
        
        // Status
        if (! empty($this->getStatus())) {
            $extra->appendChild($document->createElement('ORDERSTATUS', 'QUERY'));
            $root->appendChild($extra);
        }
        
        if (! empty($dataBill)) {
            $billTo = $document->createElement('BillTo');
            foreach ($dataBill as $id => $value) {
                $billTo->appendChild($document->createElement($id, $value));
            }
            $root->appendChild($billTo);
        }
        
        // Post to NestPay
        $headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        );
        $httpResponse = $this->httpClient->request('POST', $this->endpoint, $headers, $document->saveXML());
        return $this->response = new Response($this, $httpResponse->getBody()->getContents());
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @param string $endpoint
     */
    public function setEndpoint(string $endpoint)
    {
        $this->endpoint = $endpoint;
    }

    public function getUserName()
    {
        return $this->getParameter('username');
    }

    public function setUserName($value)
    {
        return $this->setParameter('username', $value);
    }

    public function getClientId()
    {
        return $this->getParameter('clientId');
    }

    public function setClientId($value)
    {
        return $this->setParameter('clientId', $value);
    }

    public function getPassword()
    {
        return $this->getParameter('password');
    }

    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    public function getInstallment()
    {
        return $this->getParameter('installment');
    }

    public function setInstallment($value)
    {
        return $this->setParameter('installment', $value);
    }

    public function getType()
    {
        return $this->getParameter('type');
    }

    public function setType($value)
    {
        return $this->setParameter('type', $value);
    }

    public function getMoneyPoints()
    {
        return $this->getParameter('moneypoints');
    }

    public function setMoneyPoints($value)
    {
        return $this->setParameter('moneypoints', $value);
    }

    public function getSettlement()
    {
        return $this->getParameter('settlement');
    }

    public function setSettlement($value)
    {
        return $this->setParameter('settlement', $value);
    }

    public function getStatus()
    {
        return $this->getParameter('status');
    }

    public function setStatus($value)
    {
        return $this->setParameter('status', $value);
    }

}

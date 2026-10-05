<?php

declare(strict_types=1);

namespace App\Services;

final class NesUblBuilder
{
    public function build(array $invoice,array $supplier,array $customer,array $line): string
    {
        $x=new \XMLWriter();$x->openMemory();$x->startDocument('1.0','UTF-8');
        $x->startElement('Invoice');
        $x->writeAttribute('xmlns','urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $x->writeAttribute('xmlns:cac','urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $x->writeAttribute('xmlns:cbc','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $this->v($x,'cbc:UBLVersionID','2.1');$this->v($x,'cbc:CustomizationID','TR1.2');$this->v($x,'cbc:ProfileID',$invoice['profile']);
        $this->v($x,'cbc:ID',$invoice['number']);$this->v($x,'cbc:CopyIndicator','false');$this->v($x,'cbc:UUID',$invoice['uuid']);
        $this->v($x,'cbc:IssueDate',$invoice['date']);$this->v($x,'cbc:IssueTime',$invoice['time']);$this->v($x,'cbc:InvoiceTypeCode','SATIS');
        $this->v($x,'cbc:Note',$invoice['note']);$this->v($x,'cbc:DocumentCurrencyCode','TRY');$this->v($x,'cbc:LineCountNumeric','1');
        if(($invoice['profile']??'')==='EARSIVFATURA')$this->additionalDocumentReference($x,'ELEKTRONIK',$invoice['date'],'SEND_TYPE');
        $this->party($x,'cac:AccountingSupplierParty',$supplier,true);$this->party($x,'cac:AccountingCustomerParty',$customer,false);
        $x->startElement('cac:TaxTotal');$this->money($x,'cbc:TaxAmount',$line['vat']);$x->startElement('cac:TaxSubtotal');$this->money($x,'cbc:TaxableAmount',$line['net']);$this->money($x,'cbc:TaxAmount',$line['vat']);$this->v($x,'cbc:Percent',$line['vat_rate']);$x->startElement('cac:TaxCategory');$x->startElement('cac:TaxScheme');$this->v($x,'cbc:Name','KDV');$this->v($x,'cbc:TaxTypeCode','0015');$x->endElement();$x->endElement();$x->endElement();$x->endElement();
        $x->startElement('cac:LegalMonetaryTotal');$this->money($x,'cbc:LineExtensionAmount',$line['net']);$this->money($x,'cbc:TaxExclusiveAmount',$line['net']);$this->money($x,'cbc:TaxInclusiveAmount',$line['gross']);$this->money($x,'cbc:PayableAmount',$line['gross']);$x->endElement();
        $x->startElement('cac:InvoiceLine');$this->v($x,'cbc:ID','1');$x->startElement('cbc:InvoicedQuantity');$x->writeAttribute('unitCode','C62');$x->text('1');$x->endElement();$this->money($x,'cbc:LineExtensionAmount',$line['net']);
        $x->startElement('cac:TaxTotal');$this->money($x,'cbc:TaxAmount',$line['vat']);$x->startElement('cac:TaxSubtotal');$this->money($x,'cbc:TaxableAmount',$line['net']);$this->money($x,'cbc:TaxAmount',$line['vat']);$this->v($x,'cbc:Percent',$line['vat_rate']);$x->startElement('cac:TaxCategory');$x->startElement('cac:TaxScheme');$this->v($x,'cbc:Name','KDV');$this->v($x,'cbc:TaxTypeCode','0015');$x->endElement();$x->endElement();$x->endElement();$x->endElement();
        $x->startElement('cac:Item');$this->v($x,'cbc:Name',$line['name']);$x->endElement();$x->startElement('cac:Price');$this->money($x,'cbc:PriceAmount',$line['net']);$x->endElement();$x->endElement();
        $x->endElement();$x->endDocument();return $x->outputMemory();
    }

    private function party(\XMLWriter $x,string $element,array $p,bool $supplier): void
    {
        $x->startElement($element);$x->startElement('cac:Party');$x->startElement('cac:PartyIdentification');$x->startElement('cbc:ID');$x->writeAttribute('schemeID',strlen($p['id'])===11?'TCKN':'VKN');$x->text($p['id']);$x->endElement();$x->endElement();
        $x->startElement('cac:PartyName');$this->v($x,'cbc:Name',$p['name']);$x->endElement();$x->startElement('cac:PostalAddress');$this->v($x,'cbc:StreetName',$p['address']);$this->v($x,'cbc:CitySubdivisionName',$p['district']);$this->v($x,'cbc:CityName',$p['city']);if(!empty($p['postal_code']))$this->v($x,'cbc:PostalZone',$p['postal_code']);$x->startElement('cac:Country');$this->v($x,'cbc:Name',$p['country']??'Türkiye');$x->endElement();$x->endElement();
        if(!empty($p['tax_office'])){$x->startElement('cac:PartyTaxScheme');$x->startElement('cac:TaxScheme');$this->v($x,'cbc:Name',$p['tax_office']);$x->endElement();$x->endElement();}
        if(!empty($p['phone'])||!empty($p['email'])){$x->startElement('cac:Contact');if(!empty($p['phone']))$this->v($x,'cbc:Telephone',$p['phone']);if(!empty($p['email']))$this->v($x,'cbc:ElectronicMail',$p['email']);$x->endElement();}
        if(strlen($p['id'])===11){$parts=explode(' ',trim($p['name']),2);$x->startElement('cac:Person');$this->v($x,'cbc:FirstName',$parts[0]??$p['name']);$this->v($x,'cbc:FamilyName',$parts[1]??'-');$x->endElement();}
        $x->endElement();$x->endElement();
    }
    private function v(\XMLWriter $x,string $name,string $value): void {$x->writeElement($name,$value);}
    private function money(\XMLWriter $x,string $name,string $value): void {$x->startElement($name);$x->writeAttribute('currencyID','TRY');$x->text($value);$x->endElement();}
    private function additionalDocumentReference(\XMLWriter $x,string $id,string $date,string $type): void {$x->startElement('cac:AdditionalDocumentReference');$this->v($x,'cbc:ID',$id);$this->v($x,'cbc:IssueDate',$date);$this->v($x,'cbc:DocumentTypeCode',$type);$x->endElement();}
}

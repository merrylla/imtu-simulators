<?xml version="1.0" encoding="UTF-8"?>
<wsdl:definitions xmlns:s0="http://EDTS.DirectTopUpServices.DataTypes/2006/10" xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:tm="http://microsoft.com/wsdl/mime/textMatching/" xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/" xmlns:mime="http://schemas.xmlsoap.org/wsdl/mime/" xmlns:tns="http://EDTS.DirectTopUpServices.ServiceContracts/2006/10" xmlns:s1="http://EDTS.DirectTopUpServices.DataTypes/2011/05" xmlns:s="http://www.w3.org/2001/XMLSchema" xmlns:s2="http://EDTS.DirectTopUpServices.DataTypes/2012/03" xmlns:soap12="http://schemas.xmlsoap.org/wsdl/soap12/" xmlns:http="http://schemas.xmlsoap.org/wsdl/http/" targetNamespace="http://EDTS.DirectTopUpServices.ServiceContracts/2006/10" xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/">
  <wsdl:types>
    <s:schema elementFormDefault="qualified" targetNamespace="http://EDTS.DirectTopUpServices.DataTypes/2006/10">
      <s:element name="TopUpPhoneAccountRequest" type="s0:TopUpPhoneAccountRequest"/>
      <s:complexType name="TopUpPhoneAccountRequest">
        <s:sequence>
          <s:element minOccurs="0" maxOccurs="1" name="AuthenticationToken" type="s0:AuthenticationToken"/>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="OperatorCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="PhoneNumber" type="s:string"/>
          <s:element minOccurs="1" maxOccurs="1" name="Amount" type="s:double"/>
          <s:element minOccurs="0" maxOccurs="1" name="Comment" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="SMSRequest" type="s0:SendSMSRequest"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="AuthenticationToken">
        <s:sequence>
          <s:element minOccurs="0" maxOccurs="1" name="AuthenticationID" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="AuthenticationPassword" type="s:string"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="SendSMSRequest">
        <s:sequence>
          <s:element minOccurs="0" maxOccurs="1" name="AuthenticationToken" type="s0:AuthenticationToken"/>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="OperatorCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="MessagePhoneNumber" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="MessageText" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="MessageFrom" type="s:string"/>
        </s:sequence>
      </s:complexType>
      <s:element name="TopUpPhoneAccountResult" type="s0:TopUpPhoneAccountResponse"/>
      <s:complexType name="TopUpPhoneAccountResponse">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="TopUpPhoneAccountStatus" type="s0:TopUpPhoneAccountStatus"/>
          <s:element minOccurs="0" maxOccurs="1" name="SMSStatus" type="s0:SendSMSStatus"/>
          <s:element minOccurs="0" maxOccurs="1" name="TopUpPhoneAccountAmountSent" type="s0:TopUpPhoneAccountAmountSent"/>
          <s:element minOccurs="0" maxOccurs="1" name="OperatorCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="OperatorName" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="MobileNetworkCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryName" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="MobileCountryCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="CustomerCareNumber" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="LogoURL" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="TransactionFee" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="Denominations" type="s0:Denomination"/>
          <s:element minOccurs="0" maxOccurs="1" name="MinMaxValueRange" type="s0:MinMaxValueRange"/>
          <s:element minOccurs="0" maxOccurs="1" name="TextDisclaimer" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="TextDisclaimer2" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="TextAdditional" type="s:string"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="TopUpPhoneAccountStatus">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="StatusID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="StatusDescription" type="s:string"/>
          <s:element minOccurs="1" maxOccurs="1" name="ConfirmationID" type="s:int"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="SendSMSStatus">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="StatusID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="StatusDescription" type="s:string"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="TopUpPhoneAccountAmountSent">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="Amount" type="s:double"/>
          <s:element minOccurs="1" maxOccurs="1" name="AmountExcludingTax" type="s:double"/>
          <s:element minOccurs="0" maxOccurs="1" name="TaxName" type="s:string"/>
          <s:element minOccurs="1" maxOccurs="1" name="TaxAmount" type="s:double"/>
          <s:element minOccurs="0" maxOccurs="1" name="CurrencyCode" type="s:string"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="Denomination">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="NumberOfDenominations" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="Denominations" type="s0:ArrayOfDouble"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="ArrayOfDouble">
        <s:sequence>
          <s:element minOccurs="0" maxOccurs="unbounded" name="double" type="s:double"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="MinMaxValueRange">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MinValue" type="s:double"/>
          <s:element minOccurs="1" maxOccurs="1" name="MaxValue" type="s:double"/>
        </s:sequence>
      </s:complexType>
      <s:element name="GetTargetTopUpAmountRequest" type="s0:GetTargetTopUpAmountRequest"/>
      <s:complexType name="GetTargetTopUpAmountRequest">
        <s:sequence>
          <s:element minOccurs="0" maxOccurs="1" name="AuthenticationToken" type="s0:AuthenticationToken"/>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="OperatorCode" type="s:string"/>
          <s:element minOccurs="1" maxOccurs="1" name="Amount" type="s:double"/>
        </s:sequence>
      </s:complexType>
      <s:element name="GetTargetTopUpAmountResult" type="s0:GetTargetTopUpAmountResponse"/>
      <s:complexType name="GetTargetTopUpAmountResponse">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryCode" type="s:string"/>
          <s:element minOccurs="1" maxOccurs="1" name="Amount" type="s:double"/>
          <s:element minOccurs="1" maxOccurs="1" name="AmountExcludingTax" type="s:double"/>
          <s:element minOccurs="0" maxOccurs="1" name="TaxName" type="s:string"/>
          <s:element minOccurs="1" maxOccurs="1" name="TaxAmount" type="s:double"/>
          <s:element minOccurs="0" maxOccurs="1" name="CurrencyCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="GetTargetTopUpAmountStatus" type="s0:GetTargetTopUpAmountStatus"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="GetTargetTopUpAmountStatus">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="StatusID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="StatusDescription" type="s:string"/>
        </s:sequence>
      </s:complexType>
      <s:element name="ValidatePhoneAccountRequest" type="s0:ValidatePhoneAccountRequest"/>
      <s:complexType name="ValidatePhoneAccountRequest">
        <s:sequence>
          <s:element minOccurs="0" maxOccurs="1" name="AuthenticationToken" type="s0:AuthenticationToken"/>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="OperatorCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="PhoneNumber" type="s:string"/>
          <s:element minOccurs="1" maxOccurs="1" name="Amount" type="s:double"/>
        </s:sequence>
      </s:complexType>
      <s:element name="ValidatePhoneAccountResult" type="s0:ValidatePhoneAccountResponse"/>
      <s:complexType name="ValidatePhoneAccountResponse">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="ValidatePhoneAccountStatus" type="s0:ValidatePhoneAccountStatus"/>
          <s:element minOccurs="0" maxOccurs="1" name="Denominations" type="s0:Denomination"/>
          <s:element minOccurs="0" maxOccurs="1" name="MinMaxValueRange" type="s0:MinMaxValueRange"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryName" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="MobileCountryCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="OperatorCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="OperatorName" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="MobileNetworkCode" type="s:string"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="ValidatePhoneAccountStatus">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="StatusID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="StatusDescription" type="s:string"/>
        </s:sequence>
      </s:complexType>
      <s:element name="SendSMSRequest" type="s0:SendSMSRequest"/>
      <s:element name="SendSMSResult" type="s0:SendSMSResponse"/>
      <s:complexType name="SendSMSResponse">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="SMSStatus" type="s0:SendSMSStatus"/>
        </s:sequence>
      </s:complexType>
      <s:element name="GetBalanceRequest" type="s0:GetBalanceRequest"/>
      <s:complexType name="GetBalanceRequest">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="AuthenticationToken" type="s0:AuthenticationToken"/>
        </s:sequence>
      </s:complexType>
      <s:element name="GetBalanceResult" type="s0:GetBalanceResponse"/>
      <s:complexType name="GetBalanceResponse">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="1" maxOccurs="1" name="Balance" type="s:decimal"/>
        </s:sequence>
      </s:complexType>
      <s:element name="GetProductListRequest" type="s0:GetProductListRequest"/>
      <s:complexType name="GetProductListRequest">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="AuthenticationToken" type="s0:AuthenticationToken"/>
        </s:sequence>
      </s:complexType>
      <s:element name="GetProductListResult" type="s0:GetProductListResponse"/>
      <s:complexType name="GetProductListResponse">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="Products" type="s0:ArrayOfProduct"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="ArrayOfProduct">
        <s:sequence>
          <s:element minOccurs="0" maxOccurs="unbounded" name="Product" nillable="true" type="s0:Product"/>
        </s:sequence>
      </s:complexType>
      <s:complexType name="Product">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="CountryID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryISO" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryName" type="s:string"/>
          <s:element minOccurs="1" maxOccurs="1" name="OperatorID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="OperatorCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="OperatorName" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="LogoUrl" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="CommercialCode" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="CustomerCareNumber" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="PhoneNumberMask" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" name="Denominations" type="s0:Denomination"/>
          <s:element minOccurs="0" maxOccurs="1" name="MinMaxValueRange" type="s0:MinMaxValueRange"/>
        </s:sequence>
      </s:complexType>
    </s:schema>
    <s:schema elementFormDefault="qualified" targetNamespace="http://EDTS.DirectTopUpServices.DataTypes/2011/05">
      <s:import namespace="http://EDTS.DirectTopUpServices.DataTypes/2006/10"/>
      <s:element name="request" type="s1:IsCountrySupportedByEzeOperatorRequest"/>
      <s:complexType name="IsCountrySupportedByEzeOperatorRequest">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MessageId" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="AuthenticationToken" type="s0:AuthenticationToken"/>
          <s:element minOccurs="0" maxOccurs="1" name="CountryIso" type="s:string"/>
        </s:sequence>
      </s:complexType>
      <s:element name="IsCountrySupportedByEzeOperatorResult" type="s1:IsCountrySupportedByEzeOperatorResponse"/>
      <s:complexType name="IsCountrySupportedByEzeOperatorResponse">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="MessageId" type="s:int"/>
          <s:element minOccurs="1" maxOccurs="1" name="CountryIsSupported" type="s:boolean"/>
          <s:element minOccurs="1" maxOccurs="1" name="StatusId" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="StatusDescription" type="s:string"/>
        </s:sequence>
      </s:complexType>
    </s:schema>
    <s:schema elementFormDefault="qualified" targetNamespace="http://EDTS.DirectTopUpServices.DataTypes/2012/03">
      <s:import namespace="http://EDTS.DirectTopUpServices.DataTypes/2006/10"/>
      <s:element name="request" type="s2:GetTopUpTransactionStatusRequest"/>
      <s:complexType name="GetTopUpTransactionStatusRequest">
        <s:sequence>
          <s:element minOccurs="0" maxOccurs="1" name="AuthenticationToken" type="s0:AuthenticationToken"/>
          <s:element minOccurs="1" maxOccurs="1" name="MessageID" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="PhoneNumber" type="s:string"/>
        </s:sequence>
      </s:complexType>
      <s:element name="GetTopUpTransactionStatusResult" type="s2:GetTopUpTransactionStatusResponse"/>
      <s:complexType name="GetTopUpTransactionStatusResponse">
        <s:sequence>
          <s:element minOccurs="1" maxOccurs="1" name="Status" type="s:int"/>
          <s:element minOccurs="0" maxOccurs="1" name="StatusDescription" type="s:string"/>
          <s:element minOccurs="0" maxOccurs="1" ref="s0:TopUpPhoneAccountResult"/>
          <s:element minOccurs="1" maxOccurs="1" name="TopUpTimestamp" nillable="true" type="s:dateTime"/>
        </s:sequence>
      </s:complexType>
    </s:schema>
  </wsdl:types>
  <wsdl:message name="TopUpPhoneAccountSoapIn">
    <wsdl:part name="TopUpPhoneAccountRequest" element="s0:TopUpPhoneAccountRequest"/>
  </wsdl:message>
  <wsdl:message name="TopUpPhoneAccountSoapOut">
    <wsdl:part name="TopUpPhoneAccountResult" element="s0:TopUpPhoneAccountResult"/>
  </wsdl:message>
  <wsdl:message name="GetTargetTopUpAmountSoapIn">
    <wsdl:part name="GetTargetTopUpAmountRequest" element="s0:GetTargetTopUpAmountRequest"/>
  </wsdl:message>
  <wsdl:message name="GetTargetTopUpAmountSoapOut">
    <wsdl:part name="GetTargetTopUpAmountResult" element="s0:GetTargetTopUpAmountResult"/>
  </wsdl:message>
  <wsdl:message name="ValidatePhoneAccountSoapIn">
    <wsdl:part name="ValidatePhoneAccountRequest" element="s0:ValidatePhoneAccountRequest"/>
  </wsdl:message>
  <wsdl:message name="ValidatePhoneAccountSoapOut">
    <wsdl:part name="ValidatePhoneAccountResult" element="s0:ValidatePhoneAccountResult"/>
  </wsdl:message>
  <wsdl:message name="SendSMSSoapIn">
    <wsdl:part name="SendSMSRequest" element="s0:SendSMSRequest"/>
  </wsdl:message>
  <wsdl:message name="SendSMSSoapOut">
    <wsdl:part name="SendSMSResult" element="s0:SendSMSResult"/>
  </wsdl:message>
  <wsdl:message name="GetBalanceSoapIn">
    <wsdl:part name="GetBalanceRequest" element="s0:GetBalanceRequest"/>
  </wsdl:message>
  <wsdl:message name="GetBalanceSoapOut">
    <wsdl:part name="GetBalanceResult" element="s0:GetBalanceResult"/>
  </wsdl:message>
  <wsdl:message name="GetProductListSoapIn">
    <wsdl:part name="GetProductListRequest" element="s0:GetProductListRequest"/>
  </wsdl:message>
  <wsdl:message name="GetProductListSoapOut">
    <wsdl:part name="GetProductListResult" element="s0:GetProductListResult"/>
  </wsdl:message>
  <wsdl:message name="IsCountrySupportedByEzeOperatorSoapIn">
    <wsdl:part name="request" element="s1:request"/>
  </wsdl:message>
  <wsdl:message name="IsCountrySupportedByEzeOperatorSoapOut">
    <wsdl:part name="IsCountrySupportedByEzeOperatorResult" element="s1:IsCountrySupportedByEzeOperatorResult"/>
  </wsdl:message>
  <wsdl:message name="GetTopUpTransactionStatusSoapIn">
    <wsdl:part name="request" element="s2:request"/>
  </wsdl:message>
  <wsdl:message name="GetTopUpTransactionStatusSoapOut">
    <wsdl:part name="GetTopUpTransactionStatusResult" element="s2:GetTopUpTransactionStatusResult"/>
  </wsdl:message>
  <wsdl:portType name="EDTSManager">
    <wsdl:operation name="TopUpPhoneAccount">
      <wsdl:input message="tns:TopUpPhoneAccountSoapIn"/>
      <wsdl:output message="tns:TopUpPhoneAccountSoapOut"/>
    </wsdl:operation>
    <wsdl:operation name="GetTargetTopUpAmount">
      <wsdl:input message="tns:GetTargetTopUpAmountSoapIn"/>
      <wsdl:output message="tns:GetTargetTopUpAmountSoapOut"/>
    </wsdl:operation>
    <wsdl:operation name="ValidatePhoneAccount">
      <wsdl:input message="tns:ValidatePhoneAccountSoapIn"/>
      <wsdl:output message="tns:ValidatePhoneAccountSoapOut"/>
    </wsdl:operation>
    <wsdl:operation name="SendSMS">
      <wsdl:input message="tns:SendSMSSoapIn"/>
      <wsdl:output message="tns:SendSMSSoapOut"/>
    </wsdl:operation>
    <wsdl:operation name="GetBalance">
      <wsdl:input message="tns:GetBalanceSoapIn"/>
      <wsdl:output message="tns:GetBalanceSoapOut"/>
    </wsdl:operation>
    <wsdl:operation name="GetProductList">
      <wsdl:input message="tns:GetProductListSoapIn"/>
      <wsdl:output message="tns:GetProductListSoapOut"/>
    </wsdl:operation>
    <wsdl:operation name="IsCountrySupportedByEzeOperator">
      <wsdl:input message="tns:IsCountrySupportedByEzeOperatorSoapIn"/>
      <wsdl:output message="tns:IsCountrySupportedByEzeOperatorSoapOut"/>
    </wsdl:operation>
    <wsdl:operation name="GetTopUpTransactionStatus">
      <wsdl:input message="tns:GetTopUpTransactionStatusSoapIn"/>
      <wsdl:output message="tns:GetTopUpTransactionStatusSoapOut"/>
    </wsdl:operation>
  </wsdl:portType>
  <wsdl:binding name="EDTSManager" type="tns:EDTSManager">
    <wsdl:documentation>
      <wsi:Claim conformsTo="http://ws-i.org/profiles/basic/1.1" xmlns:wsi="http://ws-i.org/schemas/conformanceClaim/"/>
    </wsdl:documentation>
    <soap:binding transport="http://schemas.xmlsoap.org/soap/http"/>
    <wsdl:operation name="TopUpPhoneAccount">
      <soap:operation soapAction="http://EDTS.DirectTopUpServices.ServiceContracts/2006/10/TopUpPhoneAccount" style="document"/>
      <wsdl:input>
        <soap:body use="literal"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="literal"/>
      </wsdl:output>
    </wsdl:operation>
    <wsdl:operation name="GetTargetTopUpAmount">
      <soap:operation soapAction="http://EDTS.DirectTopUpServices.ServiceContracts/2006/10/GetTargetTopUpAmount" style="document"/>
      <wsdl:input>
        <soap:body use="literal"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="literal"/>
      </wsdl:output>
    </wsdl:operation>
    <wsdl:operation name="ValidatePhoneAccount">
      <soap:operation soapAction="http://EDTS.DirectTopUpServices.ServiceContracts/2006/10/ValidatePhoneAccount" style="document"/>
      <wsdl:input>
        <soap:body use="literal"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="literal"/>
      </wsdl:output>
    </wsdl:operation>
    <wsdl:operation name="SendSMS">
      <soap:operation soapAction="http://EDTS.DirectTopUpServices.ServiceContracts/2006/10/SendSMS" style="document"/>
      <wsdl:input>
        <soap:body use="literal"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="literal"/>
      </wsdl:output>
    </wsdl:operation>
    <wsdl:operation name="GetBalance">
      <soap:operation soapAction="http://EDTS.DirectTopUpServices.ServiceContracts/2010/06/GetBalance" style="document"/>
      <wsdl:input>
        <soap:body use="literal"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="literal"/>
      </wsdl:output>
    </wsdl:operation>
    <wsdl:operation name="GetProductList">
      <soap:operation soapAction="http://EDTS.DirectTopUpServices.ServiceContracts/2010/07/GetProductList" style="document"/>
      <wsdl:input>
        <soap:body use="literal"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="literal"/>
      </wsdl:output>
    </wsdl:operation>
    <wsdl:operation name="IsCountrySupportedByEzeOperator">
      <soap:operation soapAction="http://EDTS.DirectTopUpServices.ServiceContracts/2011/05/IsCountrySupportedByEzeOperator" style="document"/>
      <wsdl:input>
        <soap:body use="literal"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="literal"/>
      </wsdl:output>
    </wsdl:operation>
    <wsdl:operation name="GetTopUpTransactionStatus">
      <soap:operation soapAction="http://EDTS.DirectTopUpServices.ServiceContracts/2012/04/GetTopUpTransactionStatus" style="document"/>
      <wsdl:input>
        <soap:body use="literal"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="literal"/>
      </wsdl:output>
    </wsdl:operation>
  </wsdl:binding>
  <wsdl:service name="EDTSManager">
    <wsdl:port name="EDTSManager" binding="tns:EDTSManager">
      <soap:address location="https://edts.ezedistributor.com/EDTSManager.asmx"/>
    </wsdl:port>
  </wsdl:service>
</wsdl:definitions>
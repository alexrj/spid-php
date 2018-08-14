# spid-php
Software Development Kit for easy SPID access integration with SimpleSAMLphp.

spid-php has been developed and is maintained by Michele D'Amico (@damikael), collaborator at AgID - Agenzia per l'Italia Digitale.

spid-php è uno script composer (https://getcomposer.org/) per Linux che semplifica e automatizza il processo di installazione e configurazione di SimpleSAMLphp (https://simplesamlphp.org/) per l'integrazione dell'autenticazione SPID all'interno di applicazioni PHP tramite lo spid-smart-button (https://github.com/italia/spid-smart-button). spid-php permette di realizzare un Service Provider per SPID in pochi secondi.

Durante il processo di setup lo script richiede l'inserimento delle seguenti informazioni:
* directory di installazione (directory corrente)
* directory root del webserver
* nome del link al quale collegare SimpleSAMLphp
* EntityID del service provider
* AttributeConsumingServiceIndex da richiedere all'IDP
* se inserire nella configurazione i dati dell'IDP di test (https://idp.spid.gov.it)
* se copiare nella root del webserver i file di esempio per l'integrazione del bottone
* i dati per la generazione del certificato X.509 per il service provider

e si occupa di eseguire i seguenti passi:
* scarica l'ultima versione di SimpleSAMLphp con le relative dipendenze
* scarica l'ultima versione dello spid smart button
* crea un certificato X.509 per il service provider
* scarica i metadata degli IDP di produzione tramite il metadata unico di configurazione (https://registry.spid.gov.it/metadata/idp/spid-entities-idps.xml)
* effettua tutte le necessarie configurazioni su SimpleSAMLphp
* predispone il template e le risorse grafiche dello smart button per essere utilizzate con SimpleSAMLphp

Al termine del processo di setup si potrà utilizzare il certificato X.509 creato nella directory /cert per registrare il service provider sull'ambiente di test tramite l'interfaccia di backoffice (https://idp.spid.gov.it:8080).
Se si è scelto di copiare i file di esempio, inoltre, sarà possibile verificare subito l'integrazione accedendo da web a /login.php o /user.php

## Compliance

|<img src="https://github.com/italia/spid-graphics/blob/master/spid-logos/spid-logo-c-lb.png?raw=true" width="100" /><br />_Compliance with [SPID regulations](http://www.agid.gov.it/sites/default/files/circolari/spid-regole_tecniche_v1.pdf) (for Service Providers)_|status (! = TODO)|comments|
|:---|:---|:---|
|**Metadata:**|||
|parsing of IdP XML metadata (1.2.2.4)|!|OK but could be improved (#27 and #28); the implementation is not currently checking the AgID signature, see: #33 |
|parsing of AA XML metadata (2.2.4)||Attribute Authority is unsupported|
|SP XML metadata generation (1.3.2)|!|the SP metadata is made available at the `/myservice/module.php/saml/sp/metadata.php/service-l1` endpoint; it is currently lacking the AttributeConsumingService array (#34) and the optional Organization key (#35)|
|**AuthnRequest generation (1.2.2.1):**|||
|generation of AuthnRequest XML|!|the generated AuthnRequest is not compliant (#31)|
|HTTP-Redirect binding|✓||
|HTTP-POST binding|✓||
|`AssertionConsumerServiceURL` customization||N.A.|
|`AssertionConsumerServiceIndex` customization|!|There are two acs in the SP metadata but there is no API for the use to choose|
|`AttributeConsumingServiceIndex` customization||N.A.|
|`AuthnContextClassRef` (SPID level) customization|✓|OK: via parameter to class constructor|
|`RequestedAuthnContext/@Comparison` customization||see item 4 in #31|
|`RelayState` customization (1.2.2)||N.A.|
|**Response/Assertion parsing**|||
|verification of `Response/Signature` value (if any)|?||
|verification of `Response/Signature` certificate (if any) against IdP/<s>AA metadata</s>|?||
|verification of `Assertion/Signature` value|?||
|verification of `Assertion/Signature` certificate against IdP/<s>AA metadata</s>|?||
|verification of `SubjectConfirmationData/@Recipient`|?||
|verification of `SubjectConfirmationData/@NotOnOrAfter`|?||
|verification of `SubjectConfirmationData/@InResponseTo`|?||
|verification of `Issuer`|?||
|verification of `Destination`|?||
|verification of `Conditions/@NotBefore`|?||
|verification of `Conditions/@NotOnOrAfter`|?||
|verification of `Audience`|?||
|parsing of Response with no `Assertion` (authentication/query failure)|?||
|parsing of failure `StatusCode` (Requester/Responder)|?||
|verification of `RelayState` (saml-bindings-2.0-os 3.5.3)|?||
|**Response/Assertion parsing for SSO (1.2.1, 1.2.2.2, 1.3.1):**|||
|parsing of `NameID`|?||
|parsing of `AuthnContextClassRef` (SPID level)|?||
|parsing of attributes|?||
|**Response/Assertion parsing for attribute query (2.2.2.2, 2.3.1):**|||
|parsing of attributes||Attribute Authority is unsupported|
|**LogoutRequest generation (for SP-initiated logout):**|||
|generation of LogoutRequest XML|?||
|HTTP-Redirect binding|?||
|HTTP-POST binding|?||
|**LogoutResponse parsing (for SP-initiated logout):**|||
|parsing of LogoutResponse XML|?||
|verification of `LogoutResponse/Signature` value (if any)|?||
|verification of `LogoutResponse/Signature` certificate (if any) against IdP metadata|?||
|verification of `Issuer`|?||
|verification of `Destination`|?||
|PartialLogout detection|?||
|**LogoutRequest parsing (for third-party-initiated logout):**||
|parsing of LogoutRequest XML|?||
|verification of `LogoutRequest/Signature` value (if any)|?||
|verification of `LogoutRequest/Signature` certificate (if any) against IdP metadata|?||
|verification of `Issuer`|?||
|verification of `Destination`|?||
|parsing of `NameID`|?||
|**LogoutResponse generation (for third-party-initiated logout):**||
|generation of LogoutResponse XML|?||
|HTTP-Redirect binding|?||
|HTTP-POST binding|?||
|PartialLogout customization|?||
|**AttributeQuery generation (2.2.2.1):**||
|generation of AttributeQuery XML||Attribute Authority is unsupported|
|SOAP binding (client)||Attribute Authority is unsupported|

## Requisiti
* Web server
* php >= 7
* php-xml
* Composer (https://getcomposer.org)
* OpenSSL 

## Installazione
```
# composer install
```

## Disinstallazione
```
# composer uninstall
```

## API
### Costruttore
```
new SPID_PHP(integer $spid_level)
```
$spid_level indica il livello SPID da richiedere

### isAuthenticated
```
bool isAuthenticated()
```
restituisce true se l'utente è autenticato, false altrimenti

### requireAuth
```
void requireAuth()
```
richiede che l'utente sia autenticato. Se l'utente non è autenticato mostra il pannello di scelta dell'IDP

### insertSPIDButton
```
void insertSPIDButton(string $size)
```
stampa il codice per l'inserimento dello smart button. $size specifica la dimensione del pulsante (S|M|L|XL)

### getAttributes
```
array getAttributes()
```
restituisce gli attributi dell'utente autenticato

### getAttribute
```
string getAttribute(string $attribute)
```
restituisce il valore per lo specifico attributo 

### logout
```
void logout()
```
esegue la disconnessione

### getLogoutURL
```
string getLogoutURL()
```
restituisce la url per eseguire la disconnessione


## Esempio di integrazione
```
require_once("<path to spid-php>/spid-php.php");
    
$spidsdk = new SPID_PHP(1);
if(!$spidsdk->isAuthenticated()) {
  $spidsdk->insertSPIDButton("L");
} else {
  foreach($spidsdk->getAttributes() as $attribute=>$value) {
    echo "<p>" . $attribute . ": <b>" . $value[0] . "</b></p>";
  }
}
```


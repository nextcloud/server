CREATE TABLE oauth_log 
(
    olg_id                  number,
    olg_osr_consumer_key    varchar2(64),
    olg_ost_token           varchar2(64),
    olg_ocr_consumer_key    varchar2(64),
    olg_oct_token           varchar2(64),
    olg_usa_id_ref          number,
    olg_received            varchar2(500),
    olg_sent                varchar2(500),
    olg_base_string         varchar2(500),
    olg_notes               varchar2(500),
    olg_timestamp           date default sysdate,
    olg_remote_ip           varchar2(50)
);

alter table oauth_log
  add constraint oauth_log_pk primary key (olg_id);
  

CREATE TABLE oauth_consumer_registry 
(
    ocr_id                  number,
    ocr_usa_id_ref          number,
    ocr_consumer_key        varchar2(64),
    ocr_consumer_secret     varchar2(64),
    ocr_signature_methods   varchar2(255)default 'HMAC-SHA1,PLAINTEXT',
    ocr_server_uri          varchar2(255),
    ocr_server_uri_host     varchar2(128),
    ocr_server_uri_path     varchar2(128),
    ocr_request_token_uri   varchar2(255),
    ocr_authorize_uri       varchar2(255),
    ocr_access_token_uri    varchar2(255),
    ocr_timestamp           date default sysdate
)

alter table oauth_consumer_registry
  add constraint oauth_consumer_registry_pk primary key (ocr_id);
  

CREATE TABLE oauth_consumer_token 
(
  oct_id                  number,
  oct_ocr_id_ref          number,
  oct_usa_id_ref          number,
  oct_name                varchar2(64) default '',
  oct_token               varchar2(64),
  oct_token_secret        varchar2(64),
  oct_token_type          varchar2(20), -- enum('request','authorized','access'),
  oct_token_ttl           date default  TO_DATE('9999.12.31', 'yyyy.mm.dd'),
  oct_timestamp           date default sysdate
);

alter table oauth_consumer_token
  add constraint oauth_consumer_token_pk primary key (oct_id);
  
  
CREATE TABLE oauth_server_registry 
(
    osr_id                      number,
    osr_usa_id_ref              number,
    osr_consumer_key            varchar2(64),
    osr_consumer_secret         varchar2(64),
    osr_enabled                 integer default '1',
    osr_status                  varchar2(16),
    osr_requester_name          varchar2(64),
    osr_requester_email         varchar2(64),
    osr_callback_uri            varchar2(255),
    osr_application_uri         varchar2(255),
    osr_application_title       varchar2(80),
    osr_application_descr       varchar2(500),
    osr_application_notes       varchar2(500),
    osr_application_type        varchar2(20),
    osr_application_commercial  integer default '0',
    osr_issue_date              date,
    osr_timestamp               date default sysdate
);


alter table oauth_server_registry
  add constraint oauth_server_registry_pk primary key (osr_id);
  

CREATE TABLE oauth_server_nonce 
(
  osn_id                  number,
  osn_consumer_key        varchar2(64),
  osn_token               varchar2(64),
  osn_timestamp           number,
  osn_nonce               varchar2(80)
);

alter table oauth_server_nonce
  add constraint oauth_server_nonce_pk primary key (osn_id);
  
  
CREATE TABLE oauth_server_token 
(
    ost_id                  number,
    ost_osr_id_ref          number,
    ost_usa_id_ref          number,
    ost_token               varchar2(64),
    ost_token_secret        varchar2(64),
    ost_token_type          varchar2(20), -- enum('request','access'),
    ost_authorized          integer default '0',
	  ost_referrer_host       varchar2(128),
	  ost_token_ttl           date default TO_DATE('9999.12.31', 'yyyy.mm.dd'),
    ost_timestamp           date default sysdate,
    ost_verifier            varchar2(10),
    ost_callback_url        varchar2(512)
);

alter table oauth_server_token
  add constraint oauth_server_token_pk primary key (ost_id);
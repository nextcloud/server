#
# Log table to hold all OAuth request when you enabled logging
#

CREATE TABLE oauth_log (
    olg_id                  serial primary key,
    olg_osr_consumer_key    varchar(64),
    olg_ost_token           varchar(64),
    olg_ocr_consumer_key    varchar(64),
    olg_oct_token           varchar(64),
    olg_usa_id_ref          text,
    olg_received            text not null,
    olg_sent                text not null,
    olg_base_string         text not null,
    olg_notes               text not null,
    olg_timestamp           timestamp not null default current_timestamp,
    olg_remote_ip           inet not null
);

COMMENT ON TABLE oauth_log IS 'Log table to hold all OAuth request when you enabled logging';


#
# /////////////////// CONSUMER SIDE ///////////////////
#

# This is a registry of all consumer codes we got from other servers
# The consumer_key/secret is obtained from the server
# We also register the server uri, so that we can find the consumer key and secret
# for a certain server.  From that server we can check if we have a token for a
# particular user.

CREATE TABLE oauth_consumer_registry (
    ocr_id                  serial primary key,
    ocr_usa_id_ref          text,
    ocr_consumer_key        varchar(128) not null,
    ocr_consumer_secret     varchar(128) not null,
    ocr_signature_methods   varchar(255) not null default 'HMAC-SHA1,PLAINTEXT',
    ocr_server_uri          varchar(255) not null,
    ocr_server_uri_host     varchar(128) not null,
    ocr_server_uri_path     varchar(128) not null,

    ocr_request_token_uri   varchar(255) not null,
    ocr_authorize_uri       varchar(255) not null,
    ocr_access_token_uri    varchar(255) not null,
    ocr_timestamp           timestamp not null default current_timestamp,

    unique (ocr_consumer_key, ocr_usa_id_ref, ocr_server_uri)
);

COMMENT ON TABLE oauth_consumer_registry IS 'This is a registry of all consumer codes we got from other servers';

# Table used to sign requests for sending to a server by the consumer
# The key is defined for a particular user.  Only one single named
# key is allowed per user/server combination

-- Create enum type token_type
CREATE TYPE consumer_token_type AS ENUM (
    'request',
    'authorized',
    'access'
);

CREATE TABLE oauth_consumer_token (
    oct_id                  serial primary key,
    oct_ocr_id_ref          integer not null,
    oct_usa_id_ref          text not null,
    oct_name                varchar(64) not null default '',
    oct_token               varchar(64) not null,
    oct_token_secret        varchar(64) not null,
    oct_token_type          consumer_token_type,
    oct_token_ttl           timestamp not null default timestamp '9999-12-31',
    oct_timestamp           timestamp not null default current_timestamp,

    unique (oct_ocr_id_ref, oct_token),
    unique (oct_usa_id_ref, oct_ocr_id_ref, oct_token_type, oct_name),

    foreign key (oct_ocr_id_ref) references oauth_consumer_registry (ocr_id)
        on update cascade
        on delete cascade
);


COMMENT ON TABLE oauth_consumer_token IS 'Table used to sign requests for sending to a server by the consumer';

#
# ////////////////// SERVER SIDE /////////////////
#

# Table holding consumer key/secret combos an user issued to consumers.
# Used for verification of incoming requests.

CREATE TABLE oauth_server_registry (
    osr_id                      serial primary key,
    osr_usa_id_ref              text,
    osr_consumer_key            varchar(64) not null,
    osr_consumer_secret         varchar(64) not null,
    osr_enabled                 boolean not null default true,
    osr_status                  varchar(16) not null,
    osr_requester_name          varchar(64) not null,
    osr_requester_email         varchar(64) not null,
    osr_callback_uri            varchar(255) not null,
    osr_application_uri         varchar(255) not null,
    osr_application_title       varchar(80) not null,
    osr_application_descr       text not null,
    osr_application_notes       text not null,
    osr_application_type        varchar(20) not null,
    osr_application_commercial  boolean not null default false,
    osr_issue_date              timestamp not null,
    osr_timestamp               timestamp not null default current_timestamp,

    unique (osr_consumer_key)
);


COMMENT ON TABLE oauth_server_registry IS 'Table holding consumer key/secret combos an user issued to consumers';

# Nonce used by a certain consumer, every used nonce should be unique, this prevents
# replaying attacks.  We need to store all timestamp/nonce combinations for the
# maximum timestamp received.

CREATE TABLE oauth_server_nonce (
    osn_id                  serial primary key,
    osn_consumer_key        varchar(64) not null,
    osn_token               varchar(64) not null,
    osn_timestamp           bigint not null,
    osn_nonce               varchar(80) not null,

    unique (osn_consumer_key, osn_token, osn_timestamp, osn_nonce)
);


COMMENT ON TABLE oauth_server_nonce IS 'Nonce used by a certain consumer, every used nonce should be unique, this prevents replaying attacks';

# Table used to verify signed requests sent to a server by the consumer
# When the verification is succesful then the associated user id is returned.

-- Create enum type token_type
CREATE TYPE server_token_type AS ENUM (
    'request',
    'access'
);

CREATE TABLE oauth_server_token (
    ost_id                  serial primary key,
    ost_osr_id_ref          integer not null,
    ost_usa_id_ref          text not null,
    ost_token               varchar(64) not null,
    ost_token_secret        varchar(64) not null,
    ost_token_type          server_token_type,
    ost_authorized          boolean not null default false,
	ost_referrer_host       varchar(128) not null default '',
	ost_token_ttl           timestamp not null default timestamp '9999-12-31',
    ost_timestamp           timestamp not null default current_timestamp,
    ost_verifier            char(10),
    ost_callback_url        varchar(512),

    unique (ost_token),

	foreign key (ost_osr_id_ref) references oauth_server_registry (osr_id)
        on update cascade
        on delete cascade
);


COMMENT ON TABLE oauth_server_token IS 'Table used to verify signed requests sent to a server by the consumer';

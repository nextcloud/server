CREATE TABLE 'locks' (
  'token' VARCHAR(255) NOT NULL DEFAULT '',
  'path' varchar(200) NOT NULL DEFAULT '',
  'expires' int(11) NOT NULL DEFAULT '0',
  'owner' varchar(200) DEFAULT NULL,
  'recursive' int(11) DEFAULT '0',
  'writelock' int(11) DEFAULT '0',
  'exclusivelock' int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY ('token'),
  UNIQUE ('token')
 );

CREATE TABLE 'log' (
  'timestamp' int(11) NOT NULL,
  'user' varchar(250) NOT NULL,
  'type' int(11) NOT NULL,
  'message' varchar(250) NOT NULL
);


CREATE TABLE  'properties' (
  'path' varchar(255) NOT NULL DEFAULT '',
  'name' varchar(120) NOT NULL DEFAULT '',
  'ns' varchar(120) NOT NULL DEFAULT 'DAV:',
  'value' text,
  PRIMARY KEY ('path','name','ns')
);

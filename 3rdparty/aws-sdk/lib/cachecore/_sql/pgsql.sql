CREATE TABLE "cache" (
	expires timestamp without time zone NOT NULL,
	id character(40) NOT NULL,
	data text NOT NULL
)
WITH (OIDS=TRUE);
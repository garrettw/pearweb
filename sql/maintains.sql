-- COLUMN          REFERENCES
--
-- handle          users(handle)
-- package         packages(id)

CREATE TABLE maintains (
  handle varchar(20) NOT NULL default '',
  package int(11) NOT NULL default '0',
  role enum('lead','developer','contributor','helper') NOT NULL default 'lead',
  active tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (handle,package)
);

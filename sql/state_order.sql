CREATE TABLE state_order (
  state varchar(10) NOT NULL default '',
  orderno int(11) default NULL,
  PRIMARY KEY  (state),
  KEY orderno (orderno)
);

INSERT INTO state_order VALUES ('stable', 0);
INSERT INTO state_order VALUES ('beta', 1);
INSERT INTO state_order VALUES ('alpha', 2);
INSERT INTO state_order VALUES ('snapshot', 3);
INSERT INTO state_order VALUES ('devel', 4);

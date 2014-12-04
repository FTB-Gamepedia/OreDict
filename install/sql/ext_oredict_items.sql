CREATE TABLE IF NOT EXISTS /*_*/ext_oredict_items (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(100) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `mod_name` varchar(10) NOT NULL,
  `grid_params` text NOT NULL,
  `flags` int(11) NOT NULL,
  PRIMARY KEY (`entry_id`)
) /*$wgDBTableOptions*/;

CREATE INDEX /*i*/tag_name ON /*_*/ext_oredict_items (`tag_name`);
CREATE INDEX /*i*/item_name ON /*_*/ext_oredict_items (`item_name`);
CREATE INDEX /*i*/mod_name ON /*_*/ext_oredict_items (`mod_name`);

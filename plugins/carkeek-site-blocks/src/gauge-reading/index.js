import "./editor.scss";
import "./style.scss";
import edit from "./edit";
import { registerBlockType } from "@wordpress/blocks";
import metadata from "./block.json";

registerBlockType( metadata.name, {
	edit,
	save: () => null,
} );

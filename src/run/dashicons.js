const https = require('https')
const fs = require('fs')

//const cp_url = 'https://raw.githubusercontent.com/WordPress/dashicons/master/codepoints.json';
const cp_url = 'https://raw.githubusercontent.com/WordPress/dashicons/5d77d5df36e5abd3ac83492c98bf74753d876a91/codepoints.json'; // last version known to be without gutenberg icons
const scss_path = './src/scss/variables/_dashicons.scss';
const json_path = './misc/dashicons.json';
let data = '';

// they f*cked up again...
const no_dashicons = [ 'heading', 'insert', 'saved', 'align-full-width', 'button', 'align-wide', 'ellipsis', 'html' ];

https.get(cp_url, res => {
	res.on('data', d => {
		data += d;
	});
	res.on('end',() => {
		let cp = JSON.parse(data);
		let json = {};
		let scss = `/* WordPress Dashicons Vars */
/* generated from ${cp_url} */

`;
		Object.keys(cp).forEach( k => {
			if ( no_dashicons.includes(k) ) {
				return;
			}
			let v = cp[k].toString(16);
			if ( k.indexOf('-duplicate') === -1 ) {
				scss += `$dashicon-${k}: '\\${v}';
`;
				json[k] = "\\" + v;
			}
		});
		fs.writeFileSync(scss_path,scss);
		fs.writeFileSync(json_path,JSON.stringify(json,null,2).replace('\\','\\'));
		console.log(`Saved in ${scss_path}`);
	});
});

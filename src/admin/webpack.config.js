const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

const settingsConfig = {
	...defaultConfig,
	entry: {
		index: path.resolve( __dirname, 'src/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
	},
};

// WordPress only provides this global from version 6.6 onward. Bundle the
// runtime separately so PHP can register it as a fallback on older versions.
const reactJSXRuntimePolyfill = {
	mode: defaultConfig.mode,
	target: defaultConfig.target,
	entry: {
		'react-jsx-runtime': {
			import: 'react/jsx-runtime',
		},
	},
	output: {
		path: path.resolve( __dirname, '../../assets' ),
		filename: '[name].js',
		library: {
			name: 'ReactJSXRuntime',
			type: 'window',
		},
	},
	externals: {
		react: 'React',
	},
};

module.exports = [ settingsConfig, reactJSXRuntimePolyfill ];

const path = require('path');
const webpack = require('webpack');
const webpack_configurator = require('../../../../tools/utils/scripts/webpack-configurator.js');

const assets_dir_path = path.resolve(__dirname, '../assets');

const webpack_config = {
    entry : {
        'widget-project-labeled-items': './project-labeled-items/src/index.js',
        'configure-widget'            : './configure-widget/index.js'
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: 'tlp'
    },
    resolve: {
        alias: {
            'tlp-mocks': path.resolve('../../../../src/www/themes/common/tlp/mocks/index.js'),
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_karma),
            webpack_configurator.rule_po_files,
            webpack_configurator.rule_vue_loader
        ]
    },
    plugins: [
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getVueLoaderOptionsPlugin(webpack_configurator.babel_options_karma)
    ]
};

if (process.env.NODE_ENV === 'production') {
    webpack_config.plugins = webpack_config.plugins.concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        new webpack.optimize.ModuleConcatenationPlugin()
    ]);
} else if (process.env.NODE_ENV === 'watch') {
    webpack_config.devtool = 'eval';
}

module.exports = webpack_config;

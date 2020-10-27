var path = require('path');

module.exports = {
    entry: './constructor.jsx',
    output: {
        path: path.resolve(__dirname, '../web/js/'),
        filename: 'constructor.js',
    },
    module: {
        rules: [
            {
                test: /\.(js|jsx)$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                },
            },
            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader']
            },
        ],
    },
};

/* eslint-disable import/no-extraneous-dependencies, global-require*/

/**
 * Main entrypoint location for webpack config.
 *
 * @since 2.0.0
 */

const merge = require('webpack-merge');
const { getConfig } = require('./helpers');

module.exports = (mode, optionsData = {}) => {

  // All config and default setting overrides must be provided using this object.
  const options = {
    config: {},
    entry: {},
    output: {},
    plugins: {},
    module: {},
    optimization: {},
    externals: {},
    resolve: {},
    ...optionsData,
  };

  // Append project config using getConfig helper.
  options.config = getConfig(
    optionsData.config.projectDir,
    optionsData.config.projectUrl,
    optionsData.config.projectPath,
    optionsData.config.assetsPath,
    optionsData.config.outputPath
  );

  // Get all webpack partials.
  const base = require('./base')(options);
  const project = require('./project')(options);
  const development = require('./development')(options);
  const production = require('./production')(options);
  const gutenberg = require('./gutenberg')(options);

  // Default output that is going to be merged in any env.
  const outputDefault = merge(project, base, gutenberg);

  // Output development setup by default.
  let output = merge(outputDefault, development);

  // Output production setup if mode is set inside package.json.
  if (mode === 'production') {
    output = merge(outputDefault, production);
  }

  return output;
};

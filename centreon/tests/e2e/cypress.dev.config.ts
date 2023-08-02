// import configuration from '@centreon/js-config/cypress/e2e/configuration';

// import { execSync } from 'child_process';

import { defineConfig } from 'cypress';
import createBundler from '@bahmutov/cypress-esbuild-preprocessor';
import Docker from 'dockerode';
import { addCucumberPreprocessorPlugin } from '@badeball/cypress-cucumber-preprocessor';
import webpackPreprocessor from '@cypress/webpack-preprocessor';
import createEsbuildPlugin from '@badeball/cypress-cucumber-preprocessor/esbuild';
import cypressOnFix from 'cypress-on-fix';

const docker = new Docker();

const setupNodeEvents = async (
  cypressOn: Cypress.PluginEvents,
  config: Cypress.PluginConfigOptions
): Promise<Cypress.PluginConfigOptions> => {
  const on = cypressOnFix(cypressOn);

  await addCucumberPreprocessorPlugin(on, config);

  on(
    'file:preprocessor',
    createBundler({
      plugins: [createEsbuildPlugin(config)]
    })
  );
  /*
  on(
    'file:preprocessor',
    webpackPreprocessor({
      webpackOptions: {
        module: {
          rules: [
            {
              exclude: [/node_modules/],
              test: /\.ts?$/,
              use: [
                {
                  loader: 'swc-loader'
                }
              ]
            },
            {
              test: /\.feature$/,
              use: [
                {
                  loader: '@badeball/cypress-cucumber-preprocessor/webpack',
                  options: config
                }
              ]
            }
          ]
        },
        resolve: {
          extensions: ['.ts', '.js']
        }
      }
    })
  );
  */

  on('before:browser:launch', (browser = {}, launchOptions) => {
    if ((browser as { name }).name === 'chrome') {
      launchOptions.args.push('--disable-gpu');
      launchOptions.args = launchOptions.args.filter(
        (element) => element !== '--disable-dev-shm-usage'
      );
    }

    return launchOptions;
  });

  interface PortBinding {
    destination: number;
    source: number;
  }

  interface StartContainerProps {
    image: string;
    name: string;
    portBindings: Array<PortBinding>;
  }

  interface StopContainerProps {
    name: string;
  }

  on('task', {
    startContainer: ({
      image,
      name,
      portBindings = []
    }: StartContainerProps) => {
      console.log(`Starting container ${image}`);
      return null;

      /*

      const webContainers = await docker.listContainers({
        all: true,
        filters: { name: [name] }
      });
      if (webContainers.length) {
        return webContainers[0];
      }

      const container = await docker.createContainer({
        AttachStderr: true,
        AttachStdin: false,
        AttachStdout: true,
        ExposedPorts: portBindings.reduce((accumulator, currentValue) => {
          accumulator[`${currentValue.source}/tcp`] = {};

          return accumulator;
        }, {}),
        HostConfig: {
          PortBindings: portBindings.reduce((accumulator, currentValue) => {
            accumulator[`${currentValue.source}/tcp`] = [
              {
                HostIP: '0.0.0.0',
                HostPort: `${currentValue.destination}`
              }
            ];

            return accumulator;
          }, {})
        },
        Image: image,
        OpenStdin: false,
        StdinOnce: false,
        Tty: true,
        name
      });
      console.log(`Container ${image} started`);

      await container.start();

      console.log(`Container ${image} started`);

      return container;
      */
    },
    stopContainer: ({ name }: StopContainerProps) => {
      /*
      const container = await docker.getContainer(name);

      console.log(`Stopping container ${name}`);

      await container.kill();
      await container.remove();

      console.log(`Container ${name} killed and removed`);
      */

      return null;
    }
  });

  return config;
};

const resultsFolder = `cypress/results`;
const webImageVersion = 'develop';

export default defineConfig({
  chromeWebSecurity: false,
  defaultCommandTimeout: 6000,
  e2e: {
    excludeSpecPattern: ['*.js', '*.ts', '*.md'],
    reporter: require.resolve(
      '@badeball/cypress-cucumber-preprocessor/pretty-reporter'
    ),
    setupNodeEvents,
    specPattern: '**/*.feature'
  },
  env: {
    OPENID_IMAGE_VERSION: '23.04',
    WEB_IMAGE_OS: 'alma9',
    WEB_IMAGE_VERSION: webImageVersion,
    dockerName: 'centreon-dev'
  },
  execTimeout: 60000,
  /*
  reporter: 'mochawesome',
  reporterOptions: {
    html: false,
    json: true,
    overwrite: true,
    reportDir: `${resultsFolder}/reports`,
    reportFilename: '[name]-report.json'
  },
  */
  requestTimeout: 10000,
  retries: 0,
  screenshotsFolder: `${resultsFolder}/screenshots`,
  video: true,
  videosFolder: `${resultsFolder}/videos`
});

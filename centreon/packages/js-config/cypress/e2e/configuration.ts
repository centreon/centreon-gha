/* eslint-disable @typescript-eslint/no-shadow */
/* eslint-disable @typescript-eslint/explicit-function-return-type */
/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable no-await-in-loop */
/* eslint-disable @typescript-eslint/no-unused-vars */
/* eslint-disable import/extensions */
/* eslint-disable import/no-unresolved */

import { execSync } from 'child_process';
import { promises as fs } from 'fs';
import path from 'path';

import { defineConfig } from 'cypress';
import installLogsPrinter from 'cypress-terminal-report/src/installLogsPrinter';
import { config as configDotenv } from 'dotenv';

import esbuildPreprocessor from './esbuild-preprocessor';
import plugins from './plugins';
import tasks from './tasks';
import { on } from 'process';

interface ConfigurationOptions {
  cypressFolder?: string;
  env?: Record<string, unknown>;
  envFile?: string;
  isDevelopment?: boolean;
  specPattern: string;
}

// Fonction pour capturer les retries
const captureRetries = (results: any) => {
  const testRetries: { [key: string]: boolean } = {};
  if ('runs' in results) {
    results.runs.forEach((run: any) => {
      run.tests.forEach((test: any) => {
        if (test.attempts && test.attempts.length > 1) {
          const testTitle = test.title.join(' > '); // Convert the array to a string
          testRetries[testTitle] = true;
        }
      });
    });
  }

  return testRetries;
};

// Fonction pour mettre à jour hasRetries.json avec les retries capturés
const updateHasRetriesFile = async (testRetries: {
  [key: string]: boolean;
}) => {
  const resultFilePath = path.join(
    __dirname,
    '../../../../tests/e2e/results',
    'hasRetries.json'
  );
  await fs.writeFile(resultFilePath, JSON.stringify(testRetries, null, 2));
  console.log('hasRetries.json updated with retries information.');
};

// Fonction pour attendre la génération de report.json et capturer les retries
const waitForReportAndCaptureRetries = async () => {
  const reportPath = path.join(
    __dirname,
    '../../../../tests/e2e/results/cucumber-logs',
    'report.json'
  );

  try {
    // Attendre que report.json soit généré
    while (true) {
      try {
        await fs.access(reportPath); // Vérifier l'existence de report.json
        break; // Si le fichier existe, sortir de la boucle
      } catch (error) {
        console.log('Waiting for report.json to be generated...');
        await new Promise((resolve) => setTimeout(resolve, 1000)); // Attendre 1 seconde
      }
    }

    // Une fois que report.json est généré, lire son contenu
    const reportData = await fs.readFile(reportPath, 'utf8');
    const reportResults = JSON.parse(reportData);

    // Capturer les retries à partir des résultats
    const testRetries = captureRetries(reportResults);
    console.log('Test retries captured:', testRetries);

    // Mettre à jour hasRetries.json avec les retries capturés
    await updateHasRetriesFile(testRetries);
  } catch (error) {
    console.error('Error while waiting for report.json:', error);
  }
};

// Configuration principale de Cypress
export default ({
  specPattern,
  cypressFolder,
  isDevelopment,
  env,
  envFile
}: ConfigurationOptions): Cypress.ConfigOptions => {
  if (envFile) {
    configDotenv({ path: envFile });
  }

  const resultsFolder = `${cypressFolder || '.'}/results`;

  const webImageVersion = execSync('git rev-parse --abbrev-ref HEAD')
    .toString('utf8')
    .replace(/[\n\r\s]+$/, '');

  return defineConfig({
    chromeWebSecurity: false,
    defaultCommandTimeout: 20000,
    downloadsFolder: `${resultsFolder}/downloads`,
    e2e: {
      excludeSpecPattern: ['*.js', '*.ts', '*.md'],
      fixturesFolder: 'fixtures',
      reporter: require.resolve('cypress-multi-reporters'),
      reporterOptions: {
        configFile: `${__dirname}/reporter-config.js`
      },
      setupNodeEvents: async (on, config) => {
        installLogsPrinter(on);
        await esbuildPreprocessor(on, config);
        tasks(on);

        return plugins(on, config);
      },
      specPattern,
      supportFile: 'support/e2e.{js,jsx,ts,tsx}'
    },
    env: {
      ...env,
      DATABASE_IMAGE: 'bitnami/mariadb:10.11',
      OPENID_IMAGE_VERSION: process.env.MAJOR || '24.04',
      SAML_IMAGE_VERSION: process.env.MAJOR || '24.04',
      WEB_IMAGE_OS: 'alma9',
      WEB_IMAGE_VERSION: webImageVersion
    },
    execTimeout: 60000,
    requestTimeout: 20000,
    retries: 0,
    screenshotsFolder: `${resultsFolder}/screenshots`,
    video: isDevelopment,
    videoCompression: 0,
    videosFolder: `${resultsFolder}/videos`,
    viewportHeight: 1080,
    viewportWidth: 1920
  });
};

on('after:run', async (results) => {
  await waitForReportAndCaptureRetries();
});

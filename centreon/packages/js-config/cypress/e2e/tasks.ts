/* eslint-disable no-console */
import { execSync } from 'child_process';
import { existsSync, mkdirSync } from 'fs';
import path from 'path';
const fs = require('fs');

import tar from 'tar-fs';
import {
  DockerComposeEnvironment,
  GenericContainer,
  StartedDockerComposeEnvironment,
  StartedTestContainer,
  Wait,
  getContainerRuntimeClient
} from 'testcontainers';
import { createConnection } from 'mysql2/promise';

interface Containers {
  [key: string]: StartedTestContainer;
}

export default (on: Cypress.PluginEvents): void => {
  let dockerEnvironment: StartedDockerComposeEnvironment | null = null;
  const containers: Containers = {};

  const getContainer = (containerName): StartedTestContainer => {
    let container;

    if (dockerEnvironment !== null) {
      container = dockerEnvironment.getContainer(`${containerName}-1`);
    } else if (containers[containerName]) {
      container = containers[containerName];
    } else {
      throw new Error(`Cannot get container ${containerName}`);
    }

    return container;
  };

  interface PortBinding {
    destination: number;
    source: number;
  }

  interface StartContainerProps {
    command?: string;
    image: string;
    name: string;
    portBindings: Array<PortBinding>;
  }

  interface StopContainerProps {
    name: string;
  }

  interface RetryTestInfo {
    testName: string;
    featureName: string;
  }

  on('task', {
    copyFromContainer: async ({ destination, serviceName, source }) => {
      try {
        if (dockerEnvironment !== null) {
          const container = dockerEnvironment.getContainer(`${serviceName}-1`);

          await container
            .copyArchiveFromContainer(source)
            .then((archiveStream) => {
              return new Promise<void>((resolve) => {
                const dest = tar.extract(destination);
                archiveStream.pipe(dest);
                dest.on('finish', resolve);
              });
            });
        }
      } catch (error) {
        console.error(error);
      }

      return null;
    },
    copyToContainer: async ({ destination, serviceName, source, type }) => {
      const container = getContainer(serviceName);

      if (type === 'directory') {
        await container.copyDirectoriesToContainer([
          {
            source,
            target: destination
          }
        ]);
      } else if (type === 'file') {
        await container.copyFilesToContainer([
          {
            source,
            target: destination
          }
        ]);
      }

      return null;
    },
    createDirectory: async (directoryPath: string) => {
      if (!existsSync(directoryPath)) {
        mkdirSync(directoryPath, { recursive: true });
      }

      return null;
    },
    execInContainer: async ({ command, name }) => {
      const { exitCode, output } = await getContainer(name).exec([
        'bash',
        '-c',
        `${command}${command.match(/[\n\r]/) ? '' : ' 2>&1'}`
      ]);

      return { exitCode, output };
    },
    getContainerId: (containerName: string) =>
      getContainer(containerName).getId(),
    getContainerIpAddress: (containerName: string) => {
      const container = getContainer(containerName);

      const networkNames = container.getNetworkNames();

      return container.getIpAddress(networkNames[0]);
    },
    getContainersLogs: async () => {
      try {
        const { dockerode } = (await getContainerRuntimeClient()).container;
        const loggedContainers = await dockerode.listContainers();

        return loggedContainers.reduce((acc, container) => {
          const containerName = container.Names[0].replace('/', '');
          acc[containerName] = execSync(`docker logs -t ${container.Id}`, {
            stdio: 'pipe'
          }).toString('utf8');

          return acc;
        }, {});
      } catch (error) {
        console.warn('Cannot get containers logs');
        console.warn(error);

        return null;
      }
    },
    requestOnDatabase: async ({ database, query }) => {
      let container: StartedTestContainer | null = null;

      if (dockerEnvironment !== null) {
        container = dockerEnvironment.getContainer('db-1');
      } else {
        container = getContainer('web');
      }

      const client = await createConnection({
        database,
        host: container.getHost(),
        password: 'centreon',
        port: container.getMappedPort(3306),
        user: 'centreon'
      });

      const [rows, fields] = await client.query(query);

      await client.end();

      return [rows, fields];
    },
    startContainer: async ({
      command,
      image,
      name,
      portBindings = []
    }: StartContainerProps) => {
      let container = await new GenericContainer(image).withName(name);

      portBindings.forEach(({ source, destination }) => {
        container = container.withExposedPorts({
          container: source,
          host: destination
        });
      });

      if (command) {
        container
          .withCommand(['bash', '-c', command])
          .withWaitStrategy(Wait.forSuccessfulCommand('ls'));
      }

      containers[name] = await container.start();

      return container;
    },
    startContainers: async ({
      composeFile,
      databaseImage,
      openidImage,
      profiles,
      samlImage,
      webImage
    }) => {
      try {
        const composeFileDir = path.dirname(composeFile);
        const composeFileName = path.basename(composeFile);

        dockerEnvironment = await new DockerComposeEnvironment(
          composeFileDir,
          composeFileName
        )
          .withEnvironment({
            MYSQL_IMAGE: databaseImage,
            OPENID_IMAGE: openidImage,
            SAML_IMAGE: samlImage,
            WEB_IMAGE: webImage
          })
          .withProfiles(...profiles)
          .withStartupTimeout(120000)
          .withWaitStrategy(
            'web-1',
            Wait.forAll([
              Wait.forHealthCheck(),
              Wait.forLogMessage('Centreon is ready')
            ])
          )
          .up();

        return null;
      } catch (error) {
        if (error instanceof Error) {
          console.error(error.message);
        }

        throw error;
      }
    },
    stopContainer: async ({ name }: StopContainerProps) => {
      if (containers[name]) {
        const container = containers[name];

        await container.stop();

        delete containers[name];
      }

      return null;
    },
    stopContainers: async () => {
      if (dockerEnvironment !== null) {
        await dockerEnvironment.down();

        dockerEnvironment = null;
      }

      return null;
    },
    waitOn: async (url: string) => {
      execSync(`npx wait-on ${url}`);

      return null;
    },
    writeRetryInfo({ retryTestInfo }: { retryTestInfo: RetryTestInfo }) {
      const resultsDir = path.join(__dirname, '../../../../tests/e2e/results');
      const retryReportFile = path.join(resultsDir, 'hasRetries.json');

      let currentData: RetryTestInfo[] = [];

      // Vérifier si le dossier results existe, sinon le créer de manière récursive
      if (!fs.existsSync(resultsDir)) {
        fs.mkdirSync(resultsDir, { recursive: true });
      }

      // Vérifier si le fichier hasRetries.json existe
      if (fs.existsSync(retryReportFile)) {
        // Lire les données actuelles du fichier
        const fileData = fs.readFileSync(retryReportFile, 'utf8');
        if (fileData.trim()) {
          try {
            // Parser les données JSON du fichier dans currentData
            currentData = JSON.parse(fileData) as RetryTestInfo[];
          } catch (error) {
            console.error("Erreur lors du parsing des données JSON :", error);
          }
        }
      }

      // Ajouter les nouvelles informations de retry à currentData
      currentData.push(retryTestInfo);

      // Écrire les données mises à jour dans le fichier hasRetries.json
      fs.writeFileSync(retryReportFile, JSON.stringify(currentData, null, 2));

      // Retourner null ou gérer toute autre réponse selon les besoins
      return null;
    }
  });
};

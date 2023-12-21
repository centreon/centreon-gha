import { execSync } from 'child_process';
import { existsSync, mkdirSync } from 'fs';

import Docker from 'dockerode';
import { DockerComposeEnvironment, Wait } from 'testcontainers';

export default (on: Cypress.PluginEvents): void => {
  const docker = new Docker();
  let dockerEnvironment;

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
    createDirectory: async (directoryPath: string) => {
      if (!existsSync(directoryPath)) {
        mkdirSync(directoryPath, { recursive: true });
      }

      return null;
    },
    startContainer: async ({
      image,
      name,
      portBindings = []
    }: StartContainerProps) => {
      const imageList = execSync(
        'docker image list --format "{{.Repository}}:{{.Tag}}"'
      ).toString('utf8');

      if (
        !imageList.match(
          new RegExp(
            `^${image.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&')}`,
            'm'
          )
        )
      ) {
        execSync(`docker pull ${image}`);
      }

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
                HostIP: '127.0.0.1',
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

      await container.start();

      return container;
    },
    startContainers: async ({ databaseImage, webImage }) => {
      const composeFilePath = `${__dirname}/../../../../../.github/docker/`;
      const composeFile = 'docker-compose.yml';

      dockerEnvironment = await new DockerComposeEnvironment(
        composeFilePath,
        composeFile
      )
        .withEnvironment({
          MYSQL_IMAGE: databaseImage,
          WEB_IMAGE: webImage
        })
        .withProfiles('web')
        .withWaitStrategy('web', Wait.forHealthCheck())
        .up();

      return null;
    },
    stopContainer: async ({ name }: StopContainerProps) => {
      const container = await docker.getContainer(name);
      await container.kill();
      await container.remove();

      return null;
    },
    stopContainers: async () => {
      await dockerEnvironment.down();

      return null;
    },
    waitOn: async (url: string) => {
      execSync(`npx wait-on ${url}`);

      return null;
    }
  });
};

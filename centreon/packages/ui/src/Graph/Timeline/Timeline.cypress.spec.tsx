import Timeline from './Timeline';

const data = [
  {
    start: '2024-09-09T10:57:42Z',
    end: '2024-09-09T11:15:00Z',
    color: 'green'
  },
  {
    start: '2024-09-09T11:15:00Z',
    end: '2024-09-09T11:30:00Z',
    color: 'red'
  },
  {
    start: '2024-09-09T11:30:00Z',
    end: '2024-09-09T11:45:00Z',
    color: 'gray'
  },
  {
    start: '2024-09-09T11:45:00Z',
    end: '2024-09-09T12:00:00Z',
    color: 'green'
  },
  {
    start: '2024-09-09T12:00:00Z',
    end: '2024-09-09T12:20:00Z',
    color: 'red'
  },
  {
    start: '2024-09-09T12:20:00Z',
    end: '2024-09-09T12:40:00Z',
    color: 'gray'
  },
  {
    start: '2024-09-09T12:40:00Z',
    end: '2024-09-09T12:57:42Z',
    color: 'green'
  }
];

const startDate = '2024-09-09T10:57:42Z';
const endDate = '2024-09-09T12:57:42Z';

const initialize = (): void => {
  cy.mount({
    Component: (
      <div
        style={{
          height: '120px',
          width: '100%',
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center'
        }}
      >
        <Timeline data={data} startDate={startDate} endDate={endDate} />
      </div>
    )
  });
};

describe('Timeline', () => {
  it('renders timeline chart correctly with provided data', () => {
    initialize();

    cy.makeSnapshot();
  });
});

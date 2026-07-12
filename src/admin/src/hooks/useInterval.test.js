global.wp = {
	i18n: {
		__: ( value ) => value,
	},
};

const { render } = require( '@testing-library/react' );
const useInterval = require( './useInterval' ).default;

function IntervalHarness( { callback, delay } ) {
	useInterval( callback, delay );
	return null;
}

describe( 'useInterval', () => {
	beforeEach( () => {
		jest.useFakeTimers();
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'uses the latest callback and clears the timer when disabled', () => {
		const first = jest.fn();
		const second = jest.fn();
		const rendered = render(
			<IntervalHarness callback={ first } delay={ 1000 } />
		);

		jest.advanceTimersByTime( 1000 );
		expect( first ).toHaveBeenCalledTimes( 1 );

		rendered.rerender(
			<IntervalHarness callback={ second } delay={ 1000 } />
		);
		jest.advanceTimersByTime( 1000 );
		expect( first ).toHaveBeenCalledTimes( 1 );
		expect( second ).toHaveBeenCalledTimes( 1 );

		rendered.rerender(
			<IntervalHarness callback={ second } delay={ null } />
		);
		jest.advanceTimersByTime( 2000 );
		expect( second ).toHaveBeenCalledTimes( 1 );
	} );
} );

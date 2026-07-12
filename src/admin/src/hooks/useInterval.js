import { useEffect, useRef } from '@wordpress/element';

export default function useInterval( callback, delay ) {
	const savedCallback = useRef();

	// Remember the latest callback.
	useEffect( () => {
		savedCallback.current = callback;
	}, [ callback ] );

	// Set up the interval.
	useEffect( () => {
		function tick() {
			savedCallback.current();
		}
		if ( delay !== null ) {
			const id = setInterval( tick, delay );
			return () => clearInterval( id );
		}

		return undefined;
	}, [ delay ] );
}

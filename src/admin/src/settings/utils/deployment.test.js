import { shouldShowStudioDeploymentNotice } from './deployment';

describe( 'shouldShowStudioDeploymentNotice', () => {
	it( 'shows the notice when Studio owns the deployment settings', () => {
		expect( shouldShowStudioDeploymentNotice( true, false ) ).toBe( true );
	} );

	it( 'hides the notice when Studio deployment settings are unlocked', () => {
		expect( shouldShowStudioDeploymentNotice( true, true ) ).toBe( false );
	} );

	it( 'does not show the Studio notice outside Studio', () => {
		expect( shouldShowStudioDeploymentNotice( false, true ) ).toBe( false );
		expect( shouldShowStudioDeploymentNotice( false, false ) ).toBe(
			false
		);
	} );
} );

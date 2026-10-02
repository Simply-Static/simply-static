global.wp = {
	i18n: {
		__: (value) => value,
	},
};

global.options = {
	plan: 'pro',
};

jest.mock('@wordpress/components', () => {
	const React = require('@wordpress/element');
	const wrap = (tag) => ({children, ...props}) => {
		delete props.isBlock;
		return React.createElement(tag, props, children);
	};
	const Control = ({label, value, onChange, disabled, type = 'text'}) => React.createElement(
		'label',
		null,
		label,
		React.createElement('input', {
			'aria-label': label,
			type,
			value: value ?? '',
			disabled,
			onChange: (event) => onChange?.(type === 'checkbox' ? event.target.checked : event.target.value),
		})
	);

	return {
		Animate: ({children}) => children(),
		Button: ({children, label, ...props}) => {
			delete props.icon;
			delete props.isDestructive;
			delete props.variant;
			return React.createElement('button', {'aria-label': label, ...props}, children);
		},
		Card: wrap('section'),
		CardBody: wrap('div'),
		CardHeader: wrap('header'),
		Dashicon: () => null,
		ExternalLink: wrap('a'),
		Flex: wrap('div'),
		FlexItem: wrap('div'),
		Notice: wrap('div'),
		TextControl: Control,
		ToggleControl: (props) => Control({...props, type: 'checkbox', value: undefined}),
		__experimentalInputControl: Control,
		__experimentalSpacer: () => null,
	};
});

const {fireEvent, render, screen} = require('@testing-library/react');
const {SettingsContext} = require('../context/SettingsContext');
const HideWP = require('./HideWP').default;

const createContext = () => ({
	settings: {
		custom_replacements: [
			{search: 'old.example', replace: 'new.example'},
		],
	},
	updateSetting: jest.fn(),
	saveSettings: jest.fn(),
	settingsSaved: false,
	setSettingsSaved: jest.fn(),
	isPro: () => true,
});

const renderPage = (context) => render(
	<SettingsContext.Provider value={context}>
		<HideWP />
	</SettingsContext.Provider>
);

describe('HideWP custom replacements', () => {
	it('updates, adds, and removes repeatable replacement rows', () => {
		const context = createContext();
		renderPage(context);

		fireEvent.change(screen.getByLabelText('Replace with'), {
			target: {value: 'static.example'},
		});
		expect(context.updateSetting).toHaveBeenLastCalledWith('custom_replacements', [
			{search: 'old.example', replace: 'static.example'},
		]);

		fireEvent.click(screen.getByRole('button', {name: 'Add replacement'}));
		expect(context.updateSetting).toHaveBeenLastCalledWith('custom_replacements', [
			{search: 'old.example', replace: 'static.example'},
			{search: '', replace: ''},
		]);

		fireEvent.click(screen.getAllByRole('button', {name: 'Remove replacement'})[1]);
		expect(context.updateSetting).toHaveBeenLastCalledWith('custom_replacements', [
			{search: 'old.example', replace: 'static.example'},
		]);
	});
});

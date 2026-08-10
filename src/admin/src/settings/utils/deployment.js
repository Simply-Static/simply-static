export function shouldShowStudioDeploymentNotice(
	isStudio,
	canEditDeploymentSettings
) {
	return isStudio && ! canEditDeploymentSettings;
}

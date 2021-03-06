export default LabelsController;

LabelsController.$inject = [
    '$window',
    '$element',
    'SharedPropertiesService'
];

function LabelsController(
    $window,
    $element,
    SharedPropertiesService
) {
    const self = this;

    initLabels();

    function initLabels() {
        if (self.pullRequestId && self.projectId) {
            createLabelsBox(self.pullRequestId, self.projectId, 0);
            return;
        }

        SharedPropertiesService.whenReady().then(function() {
            const pull_request = SharedPropertiesService.getPullRequest();

            createLabelsBox(
                pull_request.id,
                pull_request.repository_dest.project.id,
                pull_request.user_can_update_labels
            );
        });
    }

    function createLabelsBox(pull_request_id, project_id, user_can_update_labels) {
        $window.LabelsBox.create(
            $element[0],
            '/api/v1/pull_requests/' + pull_request_id + '/labels',
            '/api/v1/projects/' + project_id + '/labels',
            user_can_update_labels
        );
    }
}

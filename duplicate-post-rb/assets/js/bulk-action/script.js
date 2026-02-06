
(function () {

    const getSelectedPostIds = () => {
        return Array.from(
            document.querySelectorAll('tbody th.check-column input[type="checkbox"][name="post[]"]:checked')
        ).map((checkbox) => checkbox.value);
    };

    const onBulkActionClick = (event) => {
        const select = document.getElementById('bulk-action-selector-top');
        if (!select || select.value !== 'rb-duplicate-post-bulk-action') {
            return;
        };

        event.stopPropagation();
        event.preventDefault();

        const ids = getSelectedPostIds();
        if (ids && ids.length > 0) {
            window.rb_duplicate_post_dialog(ids);
        }
    };

    const action_buttons = document.querySelectorAll('#doaction.button.action[name="bulk_action"]');
    action_buttons.forEach((button) => button.addEventListener('click', onBulkActionClick, false));

})();
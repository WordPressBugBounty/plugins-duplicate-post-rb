(function (wp) {
    const { createElement } = wp.element;
    const { PluginSidebar, PluginSidebarMoreMenuItem, PluginDocumentSettingPanel, PluginPostStatusInfo } = wp.editor;
    const { registerPlugin } = wp.plugins;
    const { Button } = wp.components;
    const { useSelect } = wp.data;
    const { __ } = wp.i18n;


    const iconCopy = 'admin-page';

    const styleForBlock = {
        display: 'flex',
        alignItems: 'stretch',
        flexDirection: 'column',
        gap: 0,
        justifyContent: 'center',
        width: "100%"
    };

    const styleForDeacription = {
        textAlign: 'center',
        fontSize: '11px',
        paddingTop: 0,
        marginTop: '-2px',
    };

    const styleForButton = {
        flexGrow: 1,
        justifyContent: "center",
        with: "100%",
        height: "40px"
    };

    const duplicateHandler = ({ id = 0 }) => {
        console.log('createDuplicateHandler', id);

        if (id > 0 && typeof rb_duplicate_post_dialog === 'function') {
            rb_duplicate_post_dialog([id], 1);
        } else {
            console.warn('createDuplicateHandler: id not valid', id);
        }
    }

    const SidebarContent = () => {

        const post_id = useSelect(select => select("core/editor").getCurrentPostId());

        return createElement(
            'div',
            {
                style: {
                    ...styleForBlock,
                    padding: "16px"
                }
            },
            createElement(
                Button,
                {
                    isSecondary: true,
                    onClick: () => {
                        duplicateHandler({ id: post_id });
                    },
                    label: 'Copy',
                    style: styleForButton
                },
                __('Copy', 'duplicate-post-rb'),
            ),
            createElement('div', {
                style: styleForDeacription
            },
                'Configure this button ',
                createElement(
                    'a',
                    {
                        href: rbDuplicatePost && rbDuplicatePost.settings_url ? rbDuplicatePost.settings_url : '#',
                        target: "_blank"
                    },
                    'here'
                )
            ),
        );
    };

    const Plugin = () => {
        const post_id = useSelect(select => select("core/editor").getCurrentPostId());
        return createElement(
            wp.element.Fragment,
            null,
            createElement(
                PluginSidebarMoreMenuItem,
                {
                    target: 'rb-duplicate-post-sidebar',
                    icon: iconCopy,
                },
                'Rb Duplicate Post'
            ),

            createElement(
                PluginSidebar,
                {
                    name: 'rb-duplicate-post-sidebar',
                    title: 'Rb Duplicate Post',
                    icon: iconCopy,
                    onClick: () => {
                        duplicateHandler({ id: post_id });
                        console.log('clicked', post_id);
                    },

                },
                createElement(SidebarContent)
            )
        );
    };

    registerPlugin('rb-duplicate-post-sidebar', {
        render: Plugin,
        icon: iconCopy
    });

    const GroupOptions = () => {
        const post_id = useSelect(select => select("core/editor").getCurrentPostId());
        return createElement(
            'div',
            { className: 'rb-duplicate-post-copy-button-container', style: styleForBlock },
            createElement(
                Button,
                {
                    onClick: () => {
                        duplicateHandler({ id: post_id });
                    },
                    isSecondary: true,
                    style: styleForButton
                },
                'Copy'
            ),
            createElement('div', {
                style: styleForDeacription
            },
                'Configure this button ',
                createElement(
                    'a',
                    {
                        href: rbDuplicatePost && rbDuplicatePost.settings_url ? rbDuplicatePost.settings_url : '#',
                        target: "_blank"
                    },
                    'here'
                )
            ),
        );
    };

    const PluginGroupOptions = () =>
        createElement(
            PluginDocumentSettingPanel,
            {
                title: 'Rb Duplicate Post',
                name: 'rb-duplicate-post',
                icon: iconCopy
            },
            createElement(GroupOptions)
        );

    registerPlugin(
        'rb-duplicate-post-group',
        {
            icon: iconCopy,
            render: PluginGroupOptions,
        }
    );


    const PluginMainBlockOptions = () => {
        const post_id = useSelect(select => select("core/editor").getCurrentPostId());
        return createElement(
            PluginPostStatusInfo,
            {},
            createElement(
                'div',
                {
                    style: styleForBlock,
                },
                createElement(
                    Button,
                    { 
                        isSecondary: true,
                        className: ' ',
                        onClick: () => {
                            duplicateHandler({ id: post_id });
                        },
                        //icon: iconCopy,
                        style: styleForButton
                    },
                    'Copy'
                ),
                createElement('div', {
                    style: styleForDeacription
                },
                    'Configure this button ',
                    createElement(
                        'a',
                        {
                            href: rbDuplicatePost && rbDuplicatePost.settings_url ? rbDuplicatePost.settings_url : '#',
                            target: "_blank"
                        },
                        'here'
                    )
                ),
            )
        );
    };

    registerPlugin('rb-duplicate-post-main-block', {
        render: PluginMainBlockOptions,
    });


})(window.wp);

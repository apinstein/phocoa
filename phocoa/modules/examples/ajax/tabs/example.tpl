{* vim: set expandtab tabstop=4 shiftwidth=4 syntax=smarty: *}
<h1>YUI TabView and Tabs</h1>
<p>YUI TabView and Tab objects provide a tabbed-interface infrastructure. The following example demonstrates various features of the YUI tabview, including left-oriented tabs, disabled tabs, and dynamically loading tab content.</p>
{WFViewBlock id="tabView"}
    {WFViewBlock id="tab1"}
        Tab 1 content
    {/WFViewBlock}
    {WFViewBlock id="tab2"}
        Tab 2 content
    {/WFViewBlock}
    {WFViewBlock id="tab3"}
        Tab 3 content
    {/WFViewBlock}
    {WFViewBlock id="tab4"}
        Tab 4 content loading...
    {/WFViewBlock}
{/WFViewBlock}

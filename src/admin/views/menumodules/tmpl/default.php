<?php

// No direct access.
defined('_JEXEC') or die;

JHtml::_('behavior.formvalidation');

?>

<script type="text/template" id="menumodule_content">
    <div class="menumodule-label">
        <div class="menumodule-id">
            #<%= id %>
        </div>
        <a target="_blank" href="<%= href %><%= id %>">
            <%= title %>
        </a>
    </div>
    <div class="menumodule-switch">
        <%= status %>
    </div>
    <div class="clearfix"></div>
</script>

<fieldset id="main" class="adminForm">
    <legend>Menu-Module Assignments</legend>

    <div id="options">
        <label for="menu_items">Select Menu Item:</label>
        <select id="menu_items" name="menu_items">
            <option value="">- Select Menu Item -</option>
            <?php foreach ($this->menuTypes as $menuType) { ?>
            <optgroup label="<?php echo $menuType->title;?>">
                <?php foreach ($menuType->links as $link) { ?>
                <option value="<?php echo $link->value;?>"><?php echo $link->text;?></option>
                <?php } ?>
            </optgroup>
            <?php } ?>
        </select>
        <label for="filter_alpha">Filtering:</label>
        <input id="filter_alpha" type="text">
        <select id="filter_options">
            <option value="all">Show all modules</option>
            <option value="on">Show ON only</option>
            <option value="off">Show OFF only</option>
            <option value="dirty">Show changed only</option>
        </select>
        <select id="filter_published">
            <option value="all">Any state</option>
            <option value="1">Published</option>
            <option value="0">Unpublished</option>
        </select>
        <button id="reset_filter">Reset Filters</button>
        <div class="clearfix"></div>
        <button id="reset_dirty">Reset All Values</button>
        <button id="save" style="">Save Changes</button>
    </div>

    <div id="error_message"></div>

    <div id="list"></div>

</fieldset>

<link type="text/css" rel="stylesheet" href="<?php echo $this->assetPath;?>/css/menumodules.css">
<!-- jQuery -->
<script type="text/javascript" src="<?php echo $this->assetPath;?>/js/jquery.min.js"></script>
<!-- Backbone -->
<script type="text/javascript" src="<?php echo $this->assetPath;?>/js/underscore-min.js"></script>
<script type="text/javascript" src="<?php echo $this->assetPath;?>/js/backbone-min.js"></script>
<!-- Main script -->
<script type="text/javascript" src="<?php echo $this->assetPath;?>/js/menumodules.js"></script>
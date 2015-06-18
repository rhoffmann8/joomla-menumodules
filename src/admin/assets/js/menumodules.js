(function($) {
    'use strict';

    var Module = Backbone.Model.extend({
        // no defaults
        toggleStatus: function() {
            var statusList = ['on', 'off', 'all'];
            var newStatus = statusList[(statusList.indexOf(this.get('status'))+1) % 3];
            this.set('status', newStatus);
            return newStatus;
        },
        isActive: function() {
            return this.get('status') == 'on' || this.get('status') == 'all';
        },
        isDirty: function() {
            return this.get('originalStatus') != this.get('status');
        }
    });

    var ModuleList = Backbone.Collection.extend({
        model: Module,

        initialize: function() {
            _.bindAll(this, 'fetch', 'save', 'filterBy');
        },
        fetch: function(options) {
            options = options || {};
            options.url = '?option=com_menumodules&task=menumodules.getmenumodules&menuid=' + $('#menu_items').val();

            return Backbone.Collection.prototype.fetch.call(this, options);
        },
        parse: function(results) {
            _.each(results, function(item) {
                item.originalStatus = item.status;
            });
            return results;
        },

        save: function(attributes, options) {
            var data = this.filter(function(item) {
                return item.isDirty();
            }).map(function(item) {
                return {
                    id: item.get('id'),
                    status: item.get('status')
                };
            });

            return Backbone.ajax({
                method: 'post',
                url: '?option=com_menumodules&task=menumodules.updatemenumodules',
                data: JSON.stringify({
                    menuid: $('#menu_items').val(),
                    modules: data
                })
            });
        },

        filterBy: function(options) {
            options = options || {};
            var filters = {},
                prop;

            _.each(options, function(val, key) {
                if (val !== '') {
                    filters[key + 'Filter'] = val;
                }
            });

            // apply filter(s)
            return new ModuleList(this.filter(function(item) {
                for (prop in filters) {
                    if (!this[prop](item, filters[prop])) return false;
                }
                return true;
            }, this));
        },

        textFilter: function(item, val) {
            if (val == '') return true;
            var title = item.get('title').toLowerCase();

            if (val && !~title.indexOf(val.toLowerCase())) {
                return false;
            }

            return true;
        },

        statusFilter: function(item, val) {
            if (val == 'all') return true;
            if (val == 'dirty') return item.isDirty();
            if (val != item.get('status')) {
                return false;
            }
            return true;
        },

        publishedFilter: function(item, val) {
            if (val != 'all' && val != item.get('published')) {
                return false;
            }

            return true;
        }
    });

    var ToolbarView = Backbone.View.extend({
        el: '#options',
        events: {
            'change select#menu_items': 'reload',
            'click button#save': 'save',
            'keyup input#filter_alpha': 'filterBy',
            'change select#filter_options': 'filterBy',
            'change select#filter_published': 'filterBy',
            'click button#reset_filter': 'resetFilter',
            'click button#reset_dirty': 'resetDirty'
        },
        initialize: function() {
            _.bindAll(this, 'reload', 'save', 'filterBy', 'resetFilter', 'resetDirty');
        },
        // dispatch events to be picked up by view(s)
        reload: function() {
            this.trigger('reload');
        },
        save: function() {
            this.trigger('save');
        },
        filterBy: function() {
            this.trigger('filterBy');
        },
        resetFilter: function() {
            this.trigger('resetFilter');
        },
        resetDirty: function() {
            this.trigger('resetDirty');
        }
    });

    var ItemView = Backbone.View.extend({
        tagName: 'div',
        className: 'menumodule-item',
        events: {
            'click .menumodule-switch': 'toggleSwitch'
        },
        initialize: function() {
            _.bindAll(this, 'render', 'toggleSwitch');

            this.listenTo(this.model, 'change', this.render, this);
        },
        render: function() {
            var template = _.template($('#menumodule_content').html());

            this.$el.html(template({
                id: this.model.get('id'),
                title: this.model.get('title'),
                status: this.model.get('status'),
                href: '?option=com_modules&view=module&task=module.edit&id='
            }));

            this.$el.toggleClass('dirty', this.model.isDirty());

            return this;
        },
        toggleSwitch: function() {
            this.model.set({
                status: this.model.toggleStatus()
            });
            this.render();
        }
    });

    var ModuleListView = Backbone.View.extend({
        el: $('#list'),
        initialize: function() {
            _.bindAll(this, 'render', 'reload', 'save', 'filterBy', 'resetFilter', 'resetDirty');

            this.collection = new ModuleList();
            this.toolbar = new ToolbarView();

            this.listenTo(this.collection, 'reset', this.render, this);

            this.listenTo(this.toolbar, 'reload', this.reload, this);
            this.listenTo(this.toolbar, 'save', this.save, this);
            this.listenTo(this.toolbar, 'filterBy', this.filterBy, this);
            this.listenTo(this.toolbar, 'resetFilter', this.resetFilter, this);
            this.listenTo(this.toolbar, 'resetDirty', this.resetDirty, this);
        },
        render: function(c) { // take optional param for filtered collections
            c = c || this.collection;
            this.$el.html(c.map(function(item) {
                return new ItemView({
                    model: item
                }).render().el;
            }));
        },
        reload: function() {
            this.resetFilter(false);
            this.$el.empty();

            if (!$('#menu_items').val()) {
                return;
            }

            this.notify('Loading...');

            this.collection.fetch({
                success: function(collection, response) {
                    if (response.success == "false") {
                        collection.reset();
                        this.notify('An error occurred.');
                    } else {
                        this.notify();
                    }
                }.bind(this),
                error: function() {
                    this.collection.reset();
                    this.notify('An error occurred.');
                }.bind(this)
            });
        },
        save: function(menuId) {
            if (!this.collection.filter(function(item) { return item.isDirty(); }).length) {
                this.notify('No modules selected.');
                return;
            }
            this.notify('Saving changes...');
            this.collection.save().done(this.reload);
        },
        notify: function(msg) {
            msg = msg || '';
            $('#error_message').html(msg);
        },
        filterBy: function() {
            this.render(this.collection.filterBy({
                text: $('#filter_alpha').val(),
                status: $('#filter_options').val(),
                published: $('#filter_published').val()
            }));
        },
        resetFilter: function(reload) {
            $('#filter_options').val('all');
            $('#filter_alpha').val('');
            $('#filter_published').val('all');
            if (reload !== false) this.render();
        },
        resetDirty: function() {
            this.collection.filter(function(item) {
                return item.isDirty()
            }).each(function(item) {
                item.set('status', item.get('originalStatus'));
            });

            // preserve existing filters
            this.filterBy();
        }
    });

    var view = new ModuleListView();

})(jQuery);
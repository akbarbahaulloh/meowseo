/**
 * MeowSEO Setup Wizard JS
 */
(function($) {
    'use strict';

    var MeowWizard = {
        importId: null,
        stats: {
            posts: 0,
            terms: 0,
            redirects: 0
        },
        processed: {
            posts: 0,
            terms: 0,
            redirects: 0
        },

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Mode selection
            $('.mode-card').on('click', function() {
                $('.mode-card').removeClass('active');
                $(this).addClass('active');
                $(this).find('input').prop('checked', true);
            });

            // Separator selection
            $('.sep-option').on('click', function() {
                $('.sep-option').removeClass('active');
                $(this).addClass('active');
                $(this).find('input').prop('checked', true);
            });

            // Logo upload
            $('#meowseo-upload-logo').on('click', function(e) {
                e.preventDefault();
                var frame = wp.media({
                    title: meowseoWizard.i18n.upload_logo,
                    multiple: false
                }).on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#org_logo').val(attachment.url);
                }).open();
            });

            // Start import
            $('#meowseo-start-import').on('click', function() {
                self.startImport();
            });
        },

        startImport: function() {
            var self = this;
            var btn = $('#meowseo-start-import');
            var progress = $('#import-progress');
            var status = $('#import-status');
            var plugin = btn.data('plugin');

            btn.prop('disabled', true).text(meowseoWizard.i18n.importing);
            $('.button-next').prop('disabled', true);
            progress.show();
            status.text(meowseoWizard.i18n.starting);

            // 1. Initiate Import
            $.post(meowseoWizard.ajaxUrl, {
                action: 'meowseo_wizard_initiate_import',
                nonce: meowseoWizard.nonce,
                plugin: plugin
            }, function(response) {
                if (response.success) {
                    self.importId = response.data.import_id;
                    self.getStats();
                } else {
                    status.text('Error: ' + response.data);
                }
            });
        },

        getStats: function() {
            var self = this;
            var status = $('#import-status');

            $.post(meowseoWizard.ajaxUrl, {
                action: 'meowseo_wizard_get_import_stats',
                nonce: meowseoWizard.nonce,
                import_id: self.importId
            }, function(response) {
                if (response.success) {
                    self.stats = response.data;
                    self.importOptions();
                } else {
                    status.text('Error fetching stats');
                }
            });
        },

        importOptions: function() {
            var self = this;
            var status = $('#import-status');
            status.text(meowseoWizard.i18n.options);

            $.post(meowseoWizard.ajaxUrl, {
                action: 'meowseo_wizard_import_options',
                nonce: meowseoWizard.nonce,
                import_id: self.importId
            }, function(response) {
                self.updateProgressBar(10);
                self.importBatch('posts', 0);
            });
        },

        importBatch: function(type, offset) {
            var self = this;
            var status = $('#import-status');
            var bar = $('#import-bar');
            
            status.text(meowseoWizard.i18n[type] + ' (' + self.processed[type] + '/' + self.stats[type] + ')');

            $.post(meowseoWizard.ajaxUrl, {
                action: 'meowseo_wizard_import_batch',
                nonce: meowseoWizard.nonce,
                import_id: self.importId,
                type: type,
                offset: offset
            }, function(response) {
                if (response.success) {
                    self.processed[type] += response.data.count;
                    
                    // Calculate overall progress (options 10%, posts 50%, terms 30%, redirects 10%)
                    var progress = 10;
                    if (type === 'posts') {
                        progress += (self.processed.posts / self.stats.posts) * 50;
                    } else {
                        progress += 50;
                        if (type === 'terms') {
                            progress += (self.processed.terms / self.stats.terms) * 30;
                        } else {
                            progress += 30;
                            progress += (self.processed.redirects / self.stats.redirects) * 10;
                        }
                    }
                    
                    self.updateProgressBar(progress);

                    if (response.data.count > 0 && self.processed[type] < self.stats[type]) {
                        self.importBatch(type, self.processed[type]);
                    } else {
                        // Move to next type
                        if (type === 'posts') self.importBatch('terms', 0);
                        else if (type === 'terms') self.importBatch('redirects', 0);
                        else self.completeImport();
                    }
                }
            });
        },

        updateProgressBar: function(percent) {
            $('#import-bar').css('width', percent + '%');
        },

        completeImport: function() {
            var btn = $('#meowseo-start-import');
            var status = $('#import-status');
            
            this.updateProgressBar(100);
            status.text(meowseoWizard.i18n.complete);
            btn.text(meowseoWizard.i18n.import_success).addClass('button-disabled');
            
            // Trigger next button enable if it was disabled
            $('.button-next').prop('disabled', false);
        }
    };

    $(document).ready(function() {
        MeowWizard.init();
    });

})(jQuery);

/**
 * NMTO LearnPress Course Instances Admin JavaScript
 */

(function ($) {
  "use strict";

  var NMTO_AdminCourseInstances = {
    init: function () {
      this.bindEvents();
      this.initDateValidation();
      this.initCourseProtection();
    },

    bindEvents: function () {
      $(document).on("click", ".add-instance-btn", this.showCreateForm);
      $(document).on("click", ".cancel-instance", this.hideCreateForm);
      $(document).on(
        "submit",
        ".create-instance-form",
        this.handleCreateInstance
      );
      $(document).on("change", "#course_id", this.updateInstructorField);
      $(document).on(
        "change",
        'input[type="datetime-local"]',
        this.validateDates
      );
    },

    initDateValidation: function () {
      // Set minimum dates to today
      var today = new Date().toISOString().slice(0, 16);
      $('input[type="datetime-local"]').attr("min", today);
    },

    validateDates: function () {
      var enrollmentStart = $("#enrollment_start").val();
      var enrollmentEnd = $("#enrollment_end").val();
      var courseStart = $("#start_date").val();
      var courseEnd = $("#end_date").val();

      // Enrollment end should be after enrollment start
      if (
        enrollmentStart &&
        enrollmentEnd &&
        enrollmentEnd <= enrollmentStart
      ) {
        $("#enrollment_end").val("");
        alert("Enrollment end date must be after enrollment start date.");
        return;
      }

      // Course start should be after enrollment start
      if (enrollmentStart && courseStart && courseStart <= enrollmentStart) {
        $("#start_date").val("");
        alert("Course start date should be after enrollment start date.");
        return;
      }

      // Course end should be after course start
      if (courseStart && courseEnd && courseEnd <= courseStart) {
        $("#end_date").val("");
        alert("Course end date must be after course start date.");
        return;
      }

      // Update minimum values for dependent fields
      if (enrollmentStart) {
        $("#enrollment_end").attr("min", enrollmentStart);
        $("#start_date").attr("min", enrollmentStart);
      }

      if (enrollmentEnd) {
        $("#start_date").attr("min", enrollmentEnd);
      }

      if (courseStart) {
        $("#end_date").attr("min", courseStart);
      }
    },

    updateInstructorField: function () {
      var courseId = $(this).val();
      if (!courseId) return;

      // You could make an AJAX call here to get course-specific instructors
      // For now, we'll keep the current instructor list
    },

    showCreateForm: function (e) {
      e.preventDefault();
      $(".create-instance-form").slideDown();
      $(this).hide();
    },

    hideCreateForm: function (e) {
      e.preventDefault();
      $(".create-instance-form").slideUp();
      $(".add-instance-btn").show();
    },

    handleCreateInstance: function (e) {
      e.preventDefault();

      var form = $(this);
      var submitButton = form.find('input[type="submit"]');
      var originalText = submitButton.val();

      // Validate required fields
      var isValid = NMTO_AdminCourseInstances.validateForm(form);
      if (!isValid) {
        return false;
      }

      // Show loading state
      submitButton.val("Creating...").prop("disabled", true);
      form.addClass("nmto-loading");

      $.ajax({
        url: nmto_ajax.ajax_url,
        type: "POST",
        data:
          form.serialize() +
          "&action=create_course_instance&nonce=" +
          nmto_ajax.nonce,
        success: function (response) {
          if (response.success) {
            NMTO_AdminCourseInstances.showNotice(
              response.data.message,
              "success"
            );
            form[0].reset();
            NMTO_AdminCourseInstances.hideCreateForm();

            // Reload the page to show the new instance
            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            NMTO_AdminCourseInstances.showNotice(
              response.data.message,
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          NMTO_AdminCourseInstances.showNotice(
            "An error occurred while creating the instance.",
            "error"
          );
          console.error("Create instance error:", error);
        },
        complete: function () {
          submitButton.val(originalText).prop("disabled", false);
          form.removeClass("nmto-loading");
        },
      });

      return false;
    },

    validateForm: function (form) {
      var isValid = true;
      var errors = [];

      // Check required fields
      form.find("[required]").each(function () {
        if (!$(this).val()) {
          isValid = false;
          var label = $('label[for="' + $(this).attr("id") + '"]').text();
          errors.push(label + " is required.");
          $(this).addClass("error");
        } else {
          $(this).removeClass("error");
        }
      });

      // Validate dates
      var enrollmentStart = form.find("#enrollment_start").val();
      var enrollmentEnd = form.find("#enrollment_end").val();
      var courseStart = form.find("#start_date").val();
      var courseEnd = form.find("#end_date").val();

      if (
        enrollmentStart &&
        enrollmentEnd &&
        enrollmentEnd <= enrollmentStart
      ) {
        isValid = false;
        errors.push("Enrollment end date must be after enrollment start date.");
      }

      if (courseStart && courseEnd && courseEnd <= courseStart) {
        isValid = false;
        errors.push("Course end date must be after course start date.");
      }

      if (enrollmentEnd && courseStart && courseStart <= enrollmentEnd) {
        // This is just a warning, not an error
        console.warn("Course starts before enrollment period ends.");
      }

      if (!isValid) {
        NMTO_AdminCourseInstances.showNotice(errors.join("\\n"), "error");
      }

      return isValid;
    },

    showNotice: function (message, type) {
      // Remove existing notices
      $(".nmto-admin-notice").remove();

      var noticeClass =
        "notice nmto-admin-notice " +
        (type === "success" ? "notice-success" : "notice-error");
      var notice = $(
        '<div class="' +
          noticeClass +
          ' is-dismissible"><p>' +
          message +
          "</p></div>"
      );

      $(".wrap h1").after(notice);

      // Auto-dismiss after 5 seconds
      setTimeout(function () {
        notice.fadeOut();
      }, 5000);
    },

    formatDateTime: function (dateTimeString) {
      if (!dateTimeString) return "";

      var date = new Date(dateTimeString);
      return date.toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },

    exportInstances: function () {
      // Future feature: Export instances to CSV
      console.log("Export functionality coming soon...");
    },

    initCourseProtection: function () {
      // Add protection for course deletion when instances exist
      if (typeof pagenow !== "undefined" && pagenow === "edit-lp_course") {
        this.protectCoursesWithInstances();
      }

      // Add warning messages for individual course edit pages
      if (typeof pagenow !== "undefined" && pagenow === "lp_course") {
        this.addCourseEditWarnings();
      }
    },

    protectCoursesWithInstances: function () {
      // On course list page, add visual indicators for protected courses
      $(".wp-list-table tbody tr").each(function () {
        var $row = $(this);
        var courseId = $row.attr("id");

        if (courseId && courseId.indexOf("post-") === 0) {
          var postId = courseId.replace("post-", "");

          // Check if this course has instances (this would need to be passed from PHP)
          // For now, just add a class that we can style
          if ($row.find(".nmto-course-instances-meta").length > 0) {
            $row.addClass("nmto-protected-course");

            // Modify delete links
            $row.find(".submitdelete, .delete a").each(function () {
              var $link = $(this);
              $link.on("click", function (e) {
                e.preventDefault();
                alert(nmto_ajax.strings.cannot_delete_with_instances);
                return false;
              });
              $link.attr("title", "Cannot delete course with active instances");
              $link.css("color", "#a7aaad");
            });
          }
        }
      });
    },

    addCourseEditWarnings: function () {
      // Add visual warnings on course edit pages
      if ($(".nmto-course-instances-meta").length > 0) {
        // Course has instances, add protection notice
        var $publishBox = $("#major-publishing-actions");
        if ($publishBox.length > 0) {
          var warningHtml =
            '<div class="nmto-deletion-warning" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 10px; margin-bottom: 10px; border-radius: 4px;">' +
            "<strong>⚠️ " +
            nmto_ajax.strings.deletion_protected +
            ":</strong> This course has active instances and cannot be deleted. " +
            '<a href="' +
            nmto_ajax.admin_url +
            'admin.php?page=learnpress-course-instances">' +
            nmto_ajax.strings.manage_instances +
            "</a>" +
            "</div>";
          $publishBox.prepend(warningHtml);
        }
      }
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    NMTO_AdminCourseInstances.init();
  });

  // Make it globally available
  window.NMTO_AdminCourseInstances = NMTO_AdminCourseInstances;
})(jQuery);

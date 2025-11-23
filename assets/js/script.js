(function ($) {
  /**
   * header scroll
   */
  document.addEventListener("scroll", function () {
    var header = document.querySelector(".pc-simple-header");
    var scrollPosition = window.scrollY;

    if (scrollPosition > 50) {
      header.classList.add("is-scroll-header");
    } else {
      header.classList.remove("is-scroll-header");
    }
  });

  /**
   * Line body background scroll animation
   */
  $(document).ready(function () {
    var initialBackgroundPosition =
      parseFloat(localStorage.getItem("initialBackgroundPosition")) || -100;
    var scrollSpeed = 0.8;
    var $bodyHome = $("body.home");

    // Set and update the background position on scroll
    function updateBackgroundPosition() {
      var backgroundPosition = `center ${
        initialBackgroundPosition - $(window).scrollTop() * scrollSpeed
      }px`;
      $bodyHome.css("background-position", backgroundPosition);
    }

    // Set the initial background position
    updateBackgroundPosition();

    // Update the background position on scroll
    $(window).scroll(updateBackgroundPosition);

    // Save the initial background position to local storage if not already stored
    localStorage.setItem(
      "initialBackgroundPosition",
      initialBackgroundPosition
    );
  });

  /**
   * mouse effect image hero
   */
  if ($(".is-hero-bg").length > 0) {
    document.addEventListener("DOMContentLoaded", function () {
      const image = document.querySelector(".is-hero-bg");
      const originalTransform = window
        .getComputedStyle(image)
        .getPropertyValue("transform");
      let isMobile = window.innerWidth <= 767;

      function handleMouseMove(e) {
        if (!isMobile) {
          const {clientX, clientY} = e;
          const xOffset = (clientX / window.innerWidth - 0.5) * 50;
          const yOffset = (clientY / window.innerHeight - 0.5) * 50;

          applyTransform(
            `translate(${xOffset}px, ${yOffset}px)`,
            "transform 1s ease-out"
          );
        }
      }

      function handleMouseLeave() {
        if (!isMobile) {
          applyTransform(originalTransform, "transform 1s ease-in");
        }
      }

      function handleTransitionEnd() {
        applyTransform(originalTransform, "none");
      }

      function applyTransform(transformValue, transitionValue) {
        image.style.transition = transitionValue;
        image.style.transform = transformValue;
      }

      document.addEventListener("mousemove", handleMouseMove);
      image.addEventListener("mouseleave", handleMouseLeave);
      image.addEventListener("transitionend", handleTransitionEnd);

      window.addEventListener("resize", function () {
        isMobile = window.innerWidth <= 767;
        if (isMobile) {
          applyTransform(originalTransform, "none");
        }
      });
    });
  }

  /**
   * hash anchor adjustment
   */
  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("a[href='#']").forEach(function (link) {
      link.addEventListener("click", function (event) {
        event.preventDefault();

        var target = this.getAttribute("href").substring(2);
        var targetElement = document.getElementById(target);
        var offset = window.innerWidth < 767 ? 40 : 80;

        if (targetElement) {
          window.scrollTo({
            top:
              targetElement.getBoundingClientRect().top +
              window.scrollY -
              offset,
            behavior: "smooth",
          });
        }
      });
    });
  });

  /**
   * Close menu popup when click the menu item
   */
  function closeMenuPopup() {
    $(document).on("click", ".pc-simple-header-menu__mobile a", function (e) {
      const $menuMobile = $(".pc-simple-header-menu__mobile");
      const $menuOverlay = $("#pc-primary-overlay");
      $menuMobile.toggleClass("pc-open");
      $menuOverlay.toggleClass("pc-active");
    });
  }

  $(document).ready(function () {
    closeMenuPopup();
  });

  document.addEventListener("DOMContentLoaded", function () {
    if (typeof ep_event_booking !== "undefined") {
      ep_event_booking.enabled_guest_booking = true; // Set your desired value here
    }
  });

  /**
   * Initialize Datepicker & Timepicker
   */
  function initialize_booking_form_datepicker(
    available_dates,
    close_dates = null
  ) {
    const $datum_field = $(".pc-event-datum");
    // Destroy existing datepicker instance
    $datum_field.datepicker("destroy");

    // Datepicker
    $datum_field.datepicker({
      minDate: 0,
      beforeShowDay: function (date) {
        var dateString = $.datepicker.formatDate("yy-mm-dd", date);
        // Check if the date is in close_dates
        if (close_dates && close_dates.indexOf(dateString) !== -1) {
          return [false, "date-closed", "Closed"];
        }

        if (available_dates !== "everyday") {
          var isAvailable = available_dates.indexOf(dateString) !== -1;
          return [isAvailable, ""];
        } else {
          var day = date.getDay();

          // Only show day that not monday and tuesday
          return [day != 1 && day != 2];
        }
      },
      onSelect: function (dateText, inst) {
        let date = moment(dateText, "MMMM DD, YYYY", "de");
        let formattedDate = "";
        if (date.isValid()) {
          formattedDate = date.format("YYYYMMDD");
        }

        // Check for monthly event mode
        if ($datum_field.hasClass('pc-is-monthly-event')) {
          let selectedMonth = date.month(); // 0-based index
          let selectedYear = date.year();

          // Filter available_dates to current month
          let monthDates = [];
          if (typeof available_dates === "object" && Array.isArray(available_dates)) {
            available_dates.forEach(function (dt) {
              // dt format assumed "YYYY-MM-DD"
              let m = moment(dt, "YYYY-MM-DD");
              if (m.isValid() && m.month() === selectedMonth && m.year() === selectedYear) {
                monthDates.push(m);
              }
            });
          }
          // Sort the list of dates in ascending order
          monthDates.sort(function (a, b) {
            return a.unix() - b.unix();
          });

          if (monthDates.length > 0) {
            let first = monthDates[0];
            let last = monthDates[monthDates.length - 1];

            // Format range like "Februar 4, 2026" or "Februar 4, 2026 - Februar 7, 2026"
            let displayRange;
            if (first.isSame(last, "day")) {
              displayRange = first.format("MMMM D, YYYY");
            } else {
              displayRange = first.format("MMMM D, YYYY") + " - " + last.format("MMMM D, YYYY");
            }
            $datum_field.val(displayRange);

            // Set start & end date values for hidden fields in format 20260220T140000 (YYYYMMDDTHHMMSS)
            $('[name="start_date_time"]').val(first.format("YYYYMMDD[T]110000"));
            $('[name="end_date_time"]').val(last.format("YYYYMMDD[T]110000"));

            // Set datum_format to the formatted start date for the month/event range
            $("[name=datum_format]").val(first ? first.format("YYYYMMDD") : formattedDate);
          } else {
            // fallback - just select as normal, with Jahr
            $datum_field.val(date.format("MMMM D, YYYY"));
            $('[name="start_date_time"]').val(date.format("YYYYMMDD[T]110000"));
            $('[name="end_date_time"]').val(date.format("YYYYMMDD[T]110000"));
            $("[name=datum_format]").val(formattedDate);
          }

        } else {
          // Not monthly event: show single selected date formatted
          $datum_field.val(date.format("MMMM D, YYYY"));
          $("[name=datum_format]").val(formattedDate);
        }

        $("[name=zeit]").removeClass("pc-selected").val("");

        updateNoteCalendar();
        ajax_check_availability();
      },
    });
  }

  /**
   * Booking form configuration and event handling
   */
  function bookingFormConfig() {
    // Form group check
    $(".pc-booking-form-fields .form-group").each(function () {
      const $this = $(this);
      const $label = $this.find("label");
      const $label_html = $label.html();

      $label.remove();

      $this
        .find(".wpcf7-form-control-wrap")
        .prepend("<label>" + $label_html + "</label>");
      $this
        .find(".wpcf7-form-control-wrap")
        .html(
          "<span class='wpcf7-form-control-wrap-inner py-2 px-3'>" +
            $this.find(".wpcf7-form-control-wrap").html() +
            "</span>"
        );
    });

    const $kurse_field = $("[name=kurse]");
    const $zeit_field = $("[name=zeit]");
    const $kapazitat_field = $("[name=kapazitat]");
    const $datum_field = $(".pc-event-datum");

    $kurse_field.prepend("<option value='' selected>Kurs wählen</option>");
    $zeit_field.prepend("<option value='' selected>Zeit wählen</option>");
    $kapazitat_field.prepend(
      "<option value='' selected>Kapazitat wählen</option>"
    );

    // On change select field
    $(".wpcf7-select").on("change", function () {
      const $this = $(this);
      if ($this.val() !== "") {
        $this.addClass("pc-selected");
      } else {
        $this.removeClass("pc-selected");
      }
    });

    setTimeout(() => {
      $datum_field.prop("disabled", true);
      $zeit_field.prop("disabled", true);
      $kapazitat_field.prop("disabled", true);
    }, 300);

    // Zeit on change
    $zeit_field.on("change", function () {
      const $this = $(this);
      const timeString = $this.val();
      const [startTime, endTime] = timeString.split(" - ");

      const startTimeFormatted = startTime.replace(":", "");
      const endTimeFormatted = endTime.replace(":", "");

      document.getElementsByName("event-start-time")[0].value =
        startTimeFormatted;
      document.getElementsByName("event-end-time")[0].value = endTimeFormatted;

      // Update start_date_time and end_date_time
      const formattedDate = $("[name=datum_format]").val();
      if (formattedDate && startTimeFormatted) {
        $("[name=start_date_time]").val(
          formattedDate + "T" + startTimeFormatted + "00"
        );
        $("[name=end_date_time]").val(
          formattedDate + "T" + endTimeFormatted + "00"
        );
      }
    });

    // Event listener for kurse field change
    $(document).on("change", "[name=kurse]", function () {
      updateTitleCalendar();
      const $zeit_field = $("[name=zeit]");
      const $kapazitat_field = $("[name=kapazitat]");
      const $datum_field = $(".pc-event-datum");

      // reset the datum field
      $datum_field.val("");

      // Disable and clear the 'zeit' and 'kapazitat' fields
      $zeit_field.prop("disabled", true).removeClass("pc-selected").val("");
      $kapazitat_field.prop("disabled", true);

      // If the selected course is "Painting Night", also disable and clear the 'datum' field
      if ($(this).val() === "Painting Night") {
        $datum_field.prop("disabled", true).val("");
      } else {
        $datum_field.prop("disabled", false); // Enable the 'datum' field for other courses
      }

      // Call the function to check availability
      ajax_check_availability();
    });

    // Event listener for kapazitat field change
    $(document).on(
      "change",
      "[name=kapazitat], [name=vorname], [name=nachname]",
      function () {
        updateTitleCalendar();
      }
    );

    // Event listener for vorname field change
    // $(document).on("change", "[name=vorname]", function () {
    //   updateTitleCalendar();
    // });

    // // Event listener for vorname field change
    // $(document).on("change", "[name=nachname]", function () {
    //   updateTitleCalendar();
    // });

    $(document).on(
      "change",
      "[name=kurse], [name=kapazitat], [name=vorname], [name=nachname], [name=email-address], [name=telefonnummer], .pc-event-datum, [name=zeit]",
      function () {
        updateTitleCalendar();
        updateNoteCalendar();
      }
    );

    // Function to update title_calendar
    function updateTitleCalendar() {
      const $kurse_field = $("[name=kurse]");
      const $kapazitat_field = $("[name=kapazitat]");
      const $vorname_field = $("[name=vorname]");
      const $nachname_field = $("[name=nachname]");
      const $title_calendar_field = $("[name=title_calendar]");

      // Get the values of kurse, kapazitat, and vorname
      const kurseValue = $kurse_field.val();
      const kapazitatValue = $kapazitat_field.val();
      const vornameValue = $vorname_field.val();
      const nachnameValue = $nachname_field.val();

      // Combine values and update title_calendar
      if (vornameValue) {
        $title_calendar_field.val(
          `${vornameValue} ${nachnameValue} - ${kapazitatValue || ""} - ${
            kurseValue || ""
          }`
        );
      } else {
        $title_calendar_field.val("");
      }
    }
  }

  /**
   * Function to update note_calendar
   */
  function updateNoteCalendar() {
    const $kurse_field = $("[name=kurse]");
    const $kapazitat_field = $("[name=kapazitat]");
    const $vorname_field = $("[name=vorname]");
    const $nachname_field = $("[name=nachname]");
    const $email_field = $("[name=email-address]");
    const $telefonnummer_field = $("[name=telefonnummer]");
    const $datum_field = $(".pc-event-datum");
    const $zeit_field = $("[name=zeit]");
    const $note_calendar_field = $("[name=note_calendar]");

    // Get the values of the fields
    const vornameValue = $vorname_field.val();
    const nachnameValue = $nachname_field.val();
    const emailValue = $email_field.val();
    const telefonnummerValue = $telefonnummer_field.val();
    const kurseValue = $kurse_field.val();
    const datumValue = $datum_field.val();
    const zeitValue = $zeit_field.val();
    const kapazitatValue = $kapazitat_field.val();

    // Construct the note_calendar content with line breaks and labels
    let noteContent = "";
    if (vornameValue) {
      noteContent += `<strong>Vorname:</strong> ${vornameValue}<br>`;
    }
    if (nachnameValue) {
      noteContent += `<strong>Nachname:</strong> ${nachnameValue}<br>`;
    }
    if (emailValue) {
      noteContent += `<strong>Email-Adresse:</strong> ${emailValue}<br>`;
    }
    if (telefonnummerValue) {
      noteContent += `<strong>Telefonnummer:</strong> ${telefonnummerValue}<br>`;
    }
    if (kurseValue) {
      noteContent += `<strong>Kurse:</strong> ${kurseValue}<br>`;
    }
    if (datumValue) {
      noteContent += `<strong>Datum:</strong> ${datumValue}<br>`;
    }
    if (zeitValue) {
      noteContent += `<strong>Zeit:</strong> ${zeitValue}<br>`;
    }
    if (kapazitatValue) {
      noteContent += `<strong>Kapazität:</strong> ${kapazitatValue}<br>`;
    }

    // Update the note_calendar field
    $note_calendar_field.html(noteContent);
  }

  /**
   * Function on change time form
   */
  function select_time_form() {
    $(document).on("change", "[name=zeit]", function () {
      const $kapazitat_field = $("[name=kapazitat]");

      $kapazitat_field.prop("disabled", true);
      ajax_check_availability();
    });
  }

  /**
   * Ajax check availability
   */
  function ajax_check_availability() {
    const $zeit_field = $("[name=zeit]");
    const $kapazitat_field = $("[name=kapazitat]");
    const $datum_field = $(".pc-event-datum");
    const $zeit_parent = $('.pc-zeit').parent();

    const $booking_form = $(".pc-booking-form");
    const course = $("[name=kurse]").val();
    const datum = $("[name=datum]").val();
    const time = $zeit_field.val();

    let date = moment(datum, "MMMM DD, YYYY", "de");
    let dayName = "";
    let formattedDate = "";
    if (date.isValid()) {
      dayName = date.format("dddd");
      formattedDate = date.format("YYYYMMDD");
    }
    
    // show time parent
    $zeit_parent.show();
    $datum_field.removeClass('pc-is-monthly-event');

    if (course !== "") {
      $.ajax({
        type: "POST",
        url: _palmcode.ajaxurl,
        data: {
          course: course,
          datum: datum,
          dayName: dayName,
          formattedDate: formattedDate,
          time: time,
          action: "pc_check_availability",
        },
        statusCode: {
          400: function () {
            console.log(_palmcode.strings.error_400);
          },
          403: function () {
            console.log(_palmcode.strings.error_403);
          },
          500: function () {
            console.log(_palmcode.strings.error_500);
          },
        },
        beforeSend: function () {
          $(".pc-availability").html("");
          $booking_form.find(".pc-kapazitat").addClass("pc-processing");
          $booking_form.find(".pc-event-datum").addClass("pc-processing");
          $('.wpcf7-form-control-wrap[data-name="kapazitat"]')
            .find(".wpcf7-not-valid-tip")
            .remove();
          if (time === "") {
            $booking_form.find(".pc-zeit").addClass("pc-processing");
          }
        },
        success: function (response) {
          console.log(response);
          if (response.success) {

            // check if is montly event
            if(response.is_monthly_event){
              $zeit_parent.hide();
              $datum_field.addClass('pc-is-monthly-event');
            }

            // show time options
            if (time === "") {
              $("[name=zeit]").html(response.time_options);
            }

            $(".pc-availability").html(response.max_capacity);
            $("[name=kapazitat]").html(response.max_capacity_options);

            if (
              response.date_field === "empty" ||
              response.max_capacity_number === 0
            ) {
              $datum_field.prop("disabled", false);
              $datum_field.prop("readonly", true);

              if (response.max_capacity_number === 0) {
                $('.wpcf7-form-control-wrap[data-name="kapazitat"]').append(
                  '<span class="wpcf7-not-valid-tip" aria-hidden="true">Alles ausgebucht. Anderes Datum wählen.</span>'
                );
              }
            } else {
              $zeit_field.prop("disabled", false);
              if (response.time_field !== "empty") {
                $kapazitat_field.prop("disabled", false);
              }
            }

            initialize_booking_form_datepicker(
              response.dates,
              response.close_dates
            );
          }

          $booking_form.find(".pc-zeit").removeClass("pc-processing");
          $booking_form.find(".pc-kapazitat").removeClass("pc-processing");
          $booking_form.find(".pc-event-datum").removeClass("pc-processing");
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", error);
        },
      });
    }
  }

  /**
   * Auto selected event in form when on single event page
   */
  function auto_selected_event() {
    if ($("body").hasClass("single-em_event")) {
      // Get the current post slug from the URL
      const path = window.location.pathname;
      // Remove trailing slash, then get last path segment
      const slug = path.replace(/\/$/, '').split('/').pop();
      setTimeout(() => {
        const $kurse_field = $("[name=kurse]");
        $kurse_field.addClass("pc-selected");
        $kurse_field.val(slug);
        ajax_check_availability();
      }, 1000);
    }
  }

  /**
   * When email sent successfully
   */
  function cf7_email_sent_callback() {
    document.addEventListener(
      "wpcf7mailsent",
      function (event) {
        const form_id = event.detail.contactFormId;

        if (form_id === 320) {
          const $form_fields = $(".pc-booking-form-fields");
          $form_fields.find(".pc-availability").html("");
          const $thankyou_box = $(".pc-thankyou__box");

          $form_fields.toggleClass("d-none");
          $thankyou_box.toggleClass("d-none");

          const form_data = event.detail.inputs;
          const vorname = form_data[0].value;
          const nachname = form_data[1].value;
          const telefonnummer = form_data[2].value;
          const email = form_data[3].value;
          const kurse = form_data[4].value;
          const datum = form_data[5].value;
          const zeit = form_data[6].value;
          const kapazitat = form_data[7].value;

          $thankyou_box
            .find(".pc-reservation-name")
            .text(vorname + " " + nachname);
          $thankyou_box.find(".pc-reservation-phone").text(telefonnummer);
          $thankyou_box.find(".pc-reservation-email").text(email);
          $thankyou_box.find(".pc-reservation-date").text(datum);
          $thankyou_box.find(".pc-reservation-person").text(kapazitat);
          $thankyou_box.find(".pc-reservation-time").text(zeit);
          $thankyou_box.find(".pc-reservation-course").text(kurse);
        }else if(form_id === 2267){
          const $form_fields = $(".pc-booking-form-fields");
          $form_fields.find(".pc-availability").html("");
          const $thankyou_box = $(".pc-thankyou__box");

          $form_fields.toggleClass("d-none");
          $thankyou_box.toggleClass("d-none");
        }
      },
      false
    );
  }

  /**
   * Thank you button clicked
   */
  function thankyou_button_config() {
    // Other reservation button clicked
    $(document).on("click", ".pc-thankyou__button", function (e) {
      e.preventDefault();

      const $form_fields = $(".pc-booking-form-fields");
      const $thankyou_box = $(".pc-thankyou__box");
      const $kurse_field = $("[name=kurse]");
      const $zeit_field = $("[name=zeit]");
      const $kapazitat_field = $("[name=kapazitat]");
      const $datum_field = $("[name=datum]");

      $form_fields.toggleClass("d-none");
      $thankyou_box.toggleClass("d-none");
      $zeit_field.prop("disabled", true).removeClass("pc-selected");
      $kapazitat_field.prop("disabled", true).removeClass("pc-selected");
      $datum_field.val("").prop("readonly", false).prop("disabled", true);
      $kurse_field.removeClass("pc-selected");
      auto_selected_event();
      ajax_check_availability();
    });
  }

  /**
   * Anchor position Datenschutz
   */
  function anchor_position_datenschutz() {
    document.querySelectorAll(".index-link").forEach((link) => {
      link.addEventListener("click", function (event) {
        event.preventDefault(); // Mencegah perilaku default
        const targetId = this.getAttribute("href"); // Mendapatkan ID target dari atribut href
        const targetElement = document.querySelector(targetId); // Mendapatkan elemen target

        if (targetElement) {
          const topOffset = 32; // Jarak tambahan dari atas
          const elementPosition = targetElement.offsetTop; // Posisi elemen target dari atas halaman
          const offsetPosition = elementPosition - topOffset;

          window.scrollTo({
            top: offsetPosition,
            behavior: "smooth", // Scroll secara smooth
          });
        }
      });
    });
  }

  function event_popup_drawing_toy() {
    jQuery(document).ready(function () {
      if (jQuery(".pc-popup-button").length > 0) {
        console.log("test");
        const $button = jQuery(".pc-popup-button");

        $button.on("click", function (e) {
          const $this = jQuery(this);
          const $popup = $this.parents(".pum-container");

          if ($popup.length > 0) {
            $popup.find('.pum-close').trigger('click');
          }
        });
      }
    });
  }
	
	// 	Coupon Floating
	function toggle_coupon_box_floating() {
	  jQuery(document).ready(function () {
		jQuery(".coupon-box-floating-icon").on("click", function () {
		  const $parentBox = jQuery(this).closest(".coupon-box-floating");
		  $parentBox.toggleClass("active");
		});
	  });
	}

  $(document).ready(function () {
    bookingFormConfig();
    select_time_form();
    auto_selected_event();
    cf7_email_sent_callback();
    thankyou_button_config();
    anchor_position_datenschutz();
    event_popup_drawing_toy();
	toggle_coupon_box_floating();
  });
})(jQuery);

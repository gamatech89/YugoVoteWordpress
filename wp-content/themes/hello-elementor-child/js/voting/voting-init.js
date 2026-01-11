// In your voting-init.js file

jQuery(document).ready(function ($) {
  // Support both legacy (.cs-vote-list) and v2 (.ygv-voting-list) containers
  const $votingListContainers = $(".cs-vote-list, .ygv-voting-list");

  if ($votingListContainers.length > 0) {
    // voting_list_vars should be available from wp_localize_script
    // It should contain ajaxurl and nonce
    if (
      typeof voting_list_vars === "undefined" ||
      !voting_list_vars.ajaxurl ||
      !voting_list_vars.nonce
    ) {
      console.error(
        "FATAL: voting_list_vars (with ajaxurl and nonce) is not defined. Make sure to use wp_localize_script correctly in PHP."
      );
      // Provide a fallback for ajaxurl to prevent some errors, but nonce is critical
      window.voting_list_vars = {
        ajaxurl: "/wp-admin/admin-ajax.php", // This is a guess
        nonce: "",
      };
      if (
        typeof voting_list_vars.nonce === "undefined" ||
        voting_list_vars.nonce === ""
      ) {
        alert(
          "Voting system configuration error (nonce missing). Please contact support."
        ); // User-facing error
      }
    }

    $votingListContainers.each(function () {
      // Check if this is a v2 container
      const isV2 = $(this).hasClass("ygv-voting-list");

      if (isV2 && typeof VotingListV2 === "function") {
        new VotingListV2($(this));
      } else if (typeof VotingList === "function") {
        new VotingList($(this));
      } else {
        console.error(
          "Error: VotingList class is not defined. Ensure VotingList.js is loaded before voting-init.js and the class is globally accessible."
        );
      }
    });
  }
});

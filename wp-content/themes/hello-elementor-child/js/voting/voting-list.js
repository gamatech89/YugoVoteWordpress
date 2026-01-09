(function ($) {
  class VotingList {
    constructor($element) {
      this.$element = $element; // This is the .cs-vote-list element
      this.listId = parseInt(this.$element.data("list-id"), 10);

      this.ajaxurl =
        typeof voting_list_vars !== "undefined" && voting_list_vars.ajaxurl
          ? voting_list_vars.ajaxurl
          : "/wp-admin/admin-ajax.php";
      this.nonce =
        typeof voting_list_vars !== "undefined" && voting_list_vars.nonce
          ? voting_list_vars.nonce
          : "";
      if (!this.nonce) {
        console.warn(
          "VotingList: Nonce not found. AJAX security is compromised."
        );
      }

      let scaleFromData = parseInt(this.$element.data("voting-scale"), 10);

      let scaleFromButtons = this.$element.find(
        ".cs-voting-card__options:first .cs-voting-card__option-button"
      ).length;
      this.votingScale = scaleFromData || scaleFromButtons || 10;

      this.items = [];
      this.assignedVoteValueToItem = {};
      this.assignedItemToVoteValue = {};
      this.isLoadingScores = false;
      this.$loader = null;

      this.$videoPopup = $("#cs-video-popup");
      this.$videoIframe = $("#cs-popup-iframe");
      this.$videoOverlay = this.$videoPopup.find(".cs-popup-overlay");
      this.$videoCloseBtn = this.$videoPopup.find(".cs-popup-close");

      this.initItems();
      this.initVideoPopupGlobalListeners();
      this.loadInitialData();
    }

    initItems() {
      this.items = [];
      const self = this;
      // Find items by the new BEM block name for cards
      this.$element.find(".cs-voting-card").each(function () {
        const $card = $(this);
        const itemId = $card.data("item-id");
        if (typeof itemId !== "undefined" && typeof VotingItem === "function") {
          self.items.push(new VotingItem(itemId, $card, self));
        } else if (typeof VotingItem !== "function") {
          console.error(
            "VotingItem class is not defined. Ensure VotingItem.js is loaded."
          );
        } else {
          console.warn("Voting card found without data-item-id.", this);
        }
      });
    }

    // --- Loader Methods ---
    _createLoaderElement() {
      return $(
        '<div class="voting-list-loader" style="display: none;">Učitavanje glasova...</div>'
      ); // "Loading votes..."
    }

    _showLoader() {
      if (!this.$loader || this.$loader.length === 0) {
        this.$loader = this._createLoaderElement();
        this.$element.prepend(this.$loader);
      }
      this.$loader.show();
      this.$element.addClass("loading"); // For optional CSS: .cs-vote-list.loading .voting-card { visibility: hidden; }
    }

    _hideLoader() {
      if (this.$loader && this.$loader.length > 0) {
        this.$loader.hide();
      }
      this.$element.removeClass("loading");
    }

    // --- Initial Data Loading Orchestration ---
    loadInitialData() {
      this._showLoader();
      this.loadUserVotes() // loadUserVotes returns a jQuery promise
        .always(() => {
          // This .always() ensures that loadItemScoresAndReorder runs
          // regardless of success/failure of loadUserVotes.
          // loadItemScoresAndReorder will handle hiding the loader in its own .always()
          this.loadItemScoresAndReorder();
        });
    }

    // --- Video Popup Methods ---
    initVideoPopupGlobalListeners() {
      if (!this.$videoPopup.length || !this.$videoIframe.length) {
        return;
      }
      const closePopup = () => {
        if (this.$videoIframe.length) this.$videoIframe.attr("src", "");
        if (this.$videoPopup.length) this.$videoPopup.css("display", "none");
      };
      if (this.$videoOverlay.length)
        this.$videoOverlay
          .off("click.closeVideoPopupVotingList")
          .on("click.closeVideoPopupVotingList", closePopup);
      if (this.$videoCloseBtn.length)
        this.$videoCloseBtn
          .off("click.closeVideoPopupVotingList")
          .on("click.closeVideoPopupVotingList", closePopup);
    }

    _convertToEmbed(url) {
      console.log("Original video URL for embed conversion:", url);
      let videoId = null;
      let startSeconds = 0;

      try {
        const parsedUrl = new URL(url);

        // Extract video ID
        if (parsedUrl.hostname.includes("youtube.com")) {
          videoId = parsedUrl.searchParams.get("v");
        } else if (parsedUrl.hostname.includes("youtu.be")) {
          videoId = parsedUrl.pathname.slice(1);
        }

        // Parse time parameter (e.g., t=1m30s or t=90s)
        const tParam = parsedUrl.searchParams.get("t");
        if (tParam) {
          const match = tParam.match(/(?:(\d+)m)?(?:(\d+)s)?|(\d+)/);
          if (match) {
            const minutes = parseInt(match[1] || 0, 10);
            const seconds = parseInt(match[2] || match[3] || 0, 10);
            startSeconds = minutes * 60 + seconds;
          }
        }
      } catch (e) {
        console.warn("Invalid video URL passed to _convertToEmbed:", url);
      }

      if (!videoId) return url;

      let embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&rel=0&modestbranding=1`;
      if (startSeconds > 0) {
        embedUrl += `&start=${startSeconds}`;
      }

      return embedUrl;
    }

    requestVideoPlayback(videoUrl) {
      if (this.$videoPopup.length && this.$videoIframe.length) {
        const embedUrl = this._convertToEmbed(videoUrl);
        this.$videoIframe.attr("src", embedUrl);
        this.$videoPopup.css("display", "flex");
      } else {
        console.error("Video popup elements not found. Cannot play video.");
      }
    }

    // --- Core Vote Logic & State Management ---
    attemptToAssignVote(itemId, voteValue) {
      itemId = parseInt(itemId, 10);
      voteValue = parseInt(voteValue, 10);

      const itemCurrentlyHoldingThisValue =
        this.assignedVoteValueToItem[String(voteValue)];
      const currentValueOnThisItem =
        this.assignedItemToVoteValue[String(itemId)];

      let proceedWithServerAction = true;
      let serverAction = "submit_vote";
      let serverDataPayload = {
        // Prepare payload for server update
        voting_list_id: this.listId,
        voting_item_id: itemId,
        vote_value: voteValue, // This is the value being *newly* assigned or being removed
        nonce: this.nonce,
      };

      if (currentValueOnThisItem === voteValue) {
        // Case 1: Clicked active button on same item (DESELECT)
        this._clearVoteAssignment(itemId, voteValue); // Update local state
        serverAction = "remove_vote"; // Tell server to remove this specific vote
      } else {
        // Case 2 or 3: New vote or changing vote for this item
        if (
          itemCurrentlyHoldingThisValue &&
          itemCurrentlyHoldingThisValue !== itemId
        ) {
          // This voteValue is currently taken by a *different* item.
          const previousItemInstance = this.items.find(
            (item) => item.id === itemCurrentlyHoldingThisValue
          );
          const currentItemInstance = this.items.find(
            (item) => item.id === itemId
          );
          const prevTitle = previousItemInstance
            ? previousItemInstance.getTitle()
            : "drugom predmetu";
          const currTitle = currentItemInstance
            ? currentItemInstance.getTitle()
            : "ovom predmetu";

          if (
            !confirm(
              `Već ste dodelili ${voteValue} poena "${prevTitle}". Da li želite da promenite i dodelite ovaj broj poena "${currTitle}"?`
            )
          ) {
            proceedWithServerAction = false; // User cancelled the change
          } else {
            // User confirmed. Clear the voteValue from the other item's local state.
            // The server-side `submit_vote` (if implemented robustly) will handle freeing this vote_value.
            this._clearVoteAssignment(itemCurrentlyHoldingThisValue, voteValue);
          }
        }

        if (proceedWithServerAction) {
          // If this item (itemId) previously had a *different* vote value, clear that old assignment from local state.
          if (
            typeof currentValueOnThisItem !== "undefined" &&
            currentValueOnThisItem !== voteValue
          ) {
            this._clearVoteAssignment(itemId, currentValueOnThisItem);
          }
          // Assign the new vote in local state
          this.assignedVoteValueToItem[String(voteValue)] = itemId;
          this.assignedItemToVoteValue[String(itemId)] = voteValue;
          serverAction = "submit_vote"; // Ensure it's submit for new/changed assignment
        }
      }

      this.refreshAllButtonStates(); // Update UI based on potentially changed local state

      if (proceedWithServerAction) {
        this.sendVoteUpdateRequest(serverAction, serverDataPayload);
      }
    }

    _clearVoteAssignment(itemId, voteValue) {
      const itemIdStr = String(itemId);
      const voteValueStr = String(voteValue);
      const numericItemId = parseInt(itemId, 10);
      const numericVoteValue = parseInt(voteValue, 10);

      if (this.assignedVoteValueToItem[voteValueStr] === numericItemId) {
        delete this.assignedVoteValueToItem[voteValueStr];
      }
      if (this.assignedItemToVoteValue[itemIdStr] === numericVoteValue) {
        delete this.assignedItemToVoteValue[itemIdStr];
      }
    }

    refreshAllButtonStates() {
      this.items.forEach((itemInstance) => {
        itemInstance.updateButtonStates(
          this.assignedItemToVoteValue[String(itemInstance.id)],
          this.assignedVoteValueToItem
        );
      });
    }

    // --- Toast Notification Methods ---
    _showToast(message, type = "success", duration = 3000) {
      // Remove any existing toast
      $(".ygv-vote-toast").remove();

      const $toast = $(`
                <div class="ygv-vote-toast ygv-vote-toast--${type}">
                    <span class="ygv-vote-toast__message">${message}</span>
                </div>
            `);

      $("body").append($toast);

      // Trigger animation
      setTimeout(() => $toast.addClass("ygv-vote-toast--visible"), 10);

      // Auto-hide
      setTimeout(() => {
        $toast.removeClass("ygv-vote-toast--visible");
        setTimeout(() => $toast.remove(), 300);
      }, duration);
    }

    // --- Level Up Popup Methods ---
    _showLevelUpPopup(levelUp) {
      // Remove any existing popup
      $(".ygv-levelup-popup").remove();

      const isCategory = levelUp.type === "category";
      const mascotHtml =
        isCategory && levelUp.mascot_url
          ? `<img src="${levelUp.mascot_url}" alt="${levelUp.category_name}" class="ygv-levelup__mascot">`
          : `<div class="ygv-levelup__mascot-placeholder">🏆</div>`;

      const categoryBadge = isCategory
        ? `<span class="ygv-levelup__category" style="background: ${levelUp.color}">${levelUp.category_name}</span>`
        : "";

      const $popup = $(`
                <div class="ygv-levelup-popup">
                    <div class="ygv-levelup-overlay"></div>
                    <div class="ygv-levelup-content" style="--levelup-color: ${
                      levelUp.color || "#4457A5"
                    }">
                        <button class="ygv-levelup-close">&times;</button>
                        <div class="ygv-levelup__header">
                            <div class="ygv-levelup__mascot-circle">
                                ${mascotHtml}
                            </div>
                            <div class="ygv-levelup__stars">✨</div>
                        </div>
                        <div class="ygv-levelup__body">
                            <h2 class="ygv-levelup__title">Level Up!</h2>
                            ${categoryBadge}
                            <div class="ygv-levelup__level">
                                <span class="ygv-levelup__old">${
                                  levelUp.old_level
                                }</span>
                                <span class="ygv-levelup__arrow">→</span>
                                <span class="ygv-levelup__new">${
                                  levelUp.new_level
                                }</span>
                            </div>
                            <p class="ygv-levelup__rank">${levelUp.title}</p>
                        </div>
                        <button class="ygv-levelup__cta">Nastavi!</button>
                    </div>
                </div>
            `);

      $("body").append($popup);

      // Animate in
      setTimeout(() => $popup.addClass("ygv-levelup-popup--visible"), 10);

      // Close handlers
      $popup
        .find(".ygv-levelup-close, .ygv-levelup-overlay, .ygv-levelup__cta")
        .on("click", () => {
          $popup.removeClass("ygv-levelup-popup--visible");
          setTimeout(() => $popup.remove(), 300);
        });
    }

    _handleLevelUps(levelUps) {
      if (!levelUps || !Array.isArray(levelUps) || levelUps.length === 0)
        return;

      // Show category level-ups first, then overall
      const sorted = [...levelUps].sort((a, b) => {
        if (a.type === "category" && b.type === "overall") return -1;
        if (a.type === "overall" && b.type === "category") return 1;
        return 0;
      });

      // Show first level-up immediately, queue others with delay
      sorted.forEach((levelUp, index) => {
        setTimeout(() => this._showLevelUpPopup(levelUp), index * 4000);
      });
    }

    // --- Achievement Notification Methods ---
    _showAchievementPopup(achievement) {
      // Remove any existing popup
      $(".ygv-achievement-popup").remove();

      const $popup = $(`
        <div class="ygv-achievement-popup">
          <div class="ygv-achievement-popup-overlay"></div>
          <div class="ygv-achievement-popup-content">
            <button class="ygv-achievement-popup-close">&times;</button>
            <div class="ygv-achievement-popup__icon">${achievement.icon}</div>
            <div class="ygv-achievement-popup__stars">🎉</div>
            <h2 class="ygv-achievement-popup__title">Dostignuće Otključano!</h2>
            <h3 class="ygv-achievement-popup__name">${achievement.name}</h3>
            <p class="ygv-achievement-popup__desc">${achievement.description}</p>
            <div class="ygv-achievement-popup__xp">+${achievement.xp_reward} XP</div>
            <button class="ygv-achievement-popup__cta">Super!</button>
          </div>
        </div>
      `);

      $("body").append($popup);
      setTimeout(() => $popup.addClass("ygv-achievement-popup--visible"), 10);

      $popup
        .find(
          ".ygv-achievement-popup-close, .ygv-achievement-popup-overlay, .ygv-achievement-popup__cta"
        )
        .on("click", () => {
          $popup.removeClass("ygv-achievement-popup--visible");
          setTimeout(() => $popup.remove(), 300);
        });
    }

    _handleAchievements(achievements) {
      if (
        !achievements ||
        !Array.isArray(achievements) ||
        achievements.length === 0
      )
        return;

      // Show achievements one by one with delay
      achievements.forEach((achievement, index) => {
        setTimeout(() => this._showAchievementPopup(achievement), index * 4500);
      });
    }

    _buildVoteFeedbackMessage(data) {
      let parts = [];

      // Show vote value with bonus if applicable
      if (data.expert_bonus && data.expert_bonus > 0) {
        parts.push(
          `Tvoj glas: ${data.base_vote} (+${data.expert_bonus} bonus)`
        );
      }

      // Show XP earned
      if (data.xp_awarded && data.xp_awarded > 0) {
        parts.push(`+${data.xp_awarded} XP`);
      }

      // Show daily limit warning if reached
      if (data.xp_limit_reached) {
        parts.push("Dnevni limit XP dostignut");
      }

      return parts.join(" • ");
    }

    // --- AJAX Methods ---
    sendVoteUpdateRequest(actionType, dataPayload) {
      this._showLoader(); // Show loader for any vote update
      $.post(this.ajaxurl, { action: actionType, ...dataPayload })
        .done((response) => {
          if (!response.success) {
            alert(
              `Greška (${actionType}): ${
                response.data || "Došlo je do greške."
              }`
            );
            // Re-fetch user votes to revert UI to last known correct server state
            this.loadUserVotes().always(() => this.loadItemScoresAndReorder());
          } else {
            // Show feedback toast for successful vote submission
            if (actionType === "submit_vote" && response.data) {
              const feedbackMsg = this._buildVoteFeedbackMessage(response.data);
              if (feedbackMsg) {
                this._showToast(feedbackMsg, "success", 3500);
              }

              // Handle level-ups (show popup with mascot)
              if (
                response.data.level_ups &&
                response.data.level_ups.length > 0
              ) {
                // Delay slightly so toast shows first
                setTimeout(
                  () => this._handleLevelUps(response.data.level_ups),
                  1000
                );
              }

              // Handle achievements (show after level-ups)
              if (
                response.data.achievements &&
                response.data.achievements.length > 0
              ) {
                const levelUpDelay =
                  response.data.level_ups && response.data.level_ups.length > 0
                    ? response.data.level_ups.length * 4000 + 1500
                    : 1500;
                setTimeout(
                  () => this._handleAchievements(response.data.achievements),
                  levelUpDelay
                );
              }
            }
          }
        })
        .fail((jqXHR, textStatus, errorThrown) => {
          alert("Greška u komunikaciji sa serverom.");
          console.error(
            "AJAX Error for action:",
            actionType,
            textStatus,
            errorThrown
          );
          this.loadUserVotes().always(() => this.loadItemScoresAndReorder()); // Revert UI
        })
        .always(() => {
          // loadItemScoresAndReorder will handle hiding the loader after it completes
          if (actionType === "submit_vote" || actionType === "remove_vote") {
            this.loadItemScoresAndReorder();
          } else {
            this._hideLoader(); // Hide loader if not a score-changing action
          }
        });
    }

    loadUserVotes() {
      // Not showing loader here as loadInitialData handles it initially.
      // If called independently, could add showLoader/hideLoader.
      return $.get(this.ajaxurl, {
        action: "get_user_votes",
        voting_list_id: this.listId,
        // nonce: this.nonce // Add if your get_user_votes PHP handler uses/checks nonce
      })
        .done((response) => {
          this.assignedVoteValueToItem = {};
          this.assignedItemToVoteValue = {};
          if (
            response.success &&
            response.data &&
            Array.isArray(response.data)
          ) {
            response.data.forEach((vote) => {
              const itemId = parseInt(vote.voting_item_id, 10);
              const voteVal = parseInt(vote.vote_value, 10);
              this.assignedVoteValueToItem[String(voteVal)] = itemId;
              this.assignedItemToVoteValue[String(itemId)] = voteVal;
            });
          } else if (response.success === false) {
            console.warn(
              "Could not load user votes from server:",
              response.data
            );
          }
        })
        .fail((jqXHR, textStatus, errorThrown) => {
          console.error(
            "AJAX Error: Failed to load user votes:",
            textStatus,
            errorThrown
          );
        })
        .always(() => {
          this.refreshAllButtonStates();
        });
    }

    loadItemScoresAndReorder() {
      if (this.isLoadingScores && this.$loader && this.$loader.is(":visible")) {
        // If already loading and loader is visible, don't stack, but ensure loader will hide.
        // This needs careful thought if calls are rapid. For now, allow re-fetch if not loading.
      }
      if (this.isLoadingScores) return $.Deferred().resolve().promise();

      this.isLoadingScores = true;
      // Loader is typically shown before this sequence starts (e.g. in loadInitialData or sendVoteUpdateRequest)
      // If called standalone, uncomment:
      // this._showLoader();

      return $.get(this.ajaxurl, {
        action: "get_voting_list_totals",
        voting_list_id: this.listId,
        // nonce: this.nonce // Add if this endpoint checks nonce
      })
        .done((response) => {
          if (
            response.success &&
            response.data &&
            Array.isArray(response.data.items)
          ) {
            const scoresData = response.data.items;
            let itemDataForSorting = [];

            this.items.forEach((votingItemInstance) => {
              const scoreInfo = scoresData.find(
                (s) => parseInt(s.voting_item_id, 10) === votingItemInstance.id
              );
              const currentScore = scoreInfo
                ? parseInt(scoreInfo.total_points, 10)
                : 0;
              votingItemInstance.updateScoreDisplay(currentScore);
              itemDataForSorting.push({
                id: votingItemInstance.id,
                element: votingItemInstance.$element,
                score: currentScore,
              });
            });

            itemDataForSorting.sort((a, b) => {
              if (a.score === 0 && b.score > 0) return 1;
              if (b.score === 0 && a.score > 0) return -1;
              return b.score - a.score;
            });

            // Re-append elements in sorted order
            // jQuery's append will move existing elements if they are already in the DOM
            const sortedElements = itemDataForSorting.map(
              (item) => item.element
            );
            this.$element.append(sortedElements);

            itemDataForSorting.forEach((itemData, index) => {
              const votingItemInstance = this.items.find(
                (vi) => vi.id === itemData.id
              );
              if (votingItemInstance) {
                votingItemInstance.updateRankDisplay(index + 1);
              }
            });
          } else {
            console.warn(
              "Could not load item scores or invalid data format from server.",
              response
            );
          }
        })
        .fail((jqXHR, textStatus, errorThrown) => {
          console.error(
            "AJAX Error: Failed to load item scores for reordering:",
            textStatus,
            errorThrown
          );
        })
        .always(() => {
          this.isLoadingScores = false;
          this._hideLoader(); // Hide loader after scores processed & UI updated
        });
    }
  } // End of VotingList Class

  if (typeof window.VotingList === "undefined") {
    window.VotingList = VotingList;
  }
})(jQuery); // End of jQuery noConflict wrapper

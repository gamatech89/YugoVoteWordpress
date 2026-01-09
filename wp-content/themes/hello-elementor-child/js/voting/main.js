jQuery(document).ready(function ($) {
    class VotingItem {
        constructor($card, votingList) {
            this.$card = $card;
            this.votingList = votingList;
            this.itemId = $card.data("item-id");
            this.initEvents();
        }

        initEvents() {
            this.$card.find(".vote-btn").off("click").on("click", (event) => this.handleVote(event));
			this.$card.find('.read-more-btn').on('click', this.toggleDescription.bind(this));
        }

        handleVote(event) {
            let $button = $(event.currentTarget);
            let voteValue = $button.data("value");

            console.log("🗳 Vote clicked:", this.itemId, voteValue);

            // If clicking on an already active vote, remove it
            if ($button.hasClass("cs-vote-active")) {
                this.clearVote(voteValue, true);  // Send request to delete vote
                return;
            }

            let existingVote = this.votingList.getActiveVote(voteValue);
            let previousItem = existingVote ? existingVote : null;

            if (previousItem && previousItem !== this) {
                let previousTitle = previousItem.$card.find(".title").text();
                let newTitle = this.$card.find(".title").text();

                if (!confirm(`Već ste dodelili ${voteValue} poena "${previousTitle}". Da li želite da promenite i dodelite ovaj broj poena "${newTitle}"?`)) {
                    return;
                }

                previousItem.clearVote(voteValue, false);
            }

            this.applyVote($button, voteValue);
        }
		
		toggleDescription(event) {
            event.preventDefault();

			const $descContainer = $(event.currentTarget).closest('.description-container');
			$descContainer.toggleClass('expanded');
			console.log('clicked expande')
       
        }

        applyVote($button, voteValue) {
            let $card = this.$card;

            // Remove active class from any previously selected button
            $card.find(".cs-vote-active").removeClass("cs-vote-active");

            // Enable previously disabled buttons across all cards
            $(".vote-btn[data-value='" + voteValue + "']").removeClass("cs-vote-disabled");

            // Apply active class to the new vote
            $button.addClass("cs-vote-active");

            // Disable this vote value on all other cards except the selected one
            $(".vote-btn[data-value='" + voteValue + "']").not($button).addClass("cs-vote-disabled");

            // Submit vote to the server
            this.votingList.submitVote(this.itemId, voteValue);
        }

        clearVote(voteValue, shouldRemoveVote = false) {
            this.$card.find(`.vote-btn[data-value="${voteValue}"]`).removeClass("cs-vote-active");

            // Enable this vote value across all items
            $(".vote-btn[data-value='" + voteValue + "']").removeClass("cs-vote-disabled");

            // If removing the vote, send request to delete it
            if (shouldRemoveVote) {
                this.votingList.removeVote(this.itemId, voteValue);
            }
        }

        loadVote(voteValue) {
            let $button = this.$card.find(`.vote-btn[data-value="${voteValue}"]`);
            if ($button.length) {
                this.applyVote($button, voteValue);
            }
        }
    }

    class VotingList {
        constructor() {
            this.votingListId = $(".cs-vote-list").data("list-id");
            this.items = [];
            this.isLoadingScores = false;
            this.initItems();
            this.loadUserVotes();
            this.loadVotingScores();
        }

        initItems() {
            $(".voting-card").each((_, card) => {
                let item = new VotingItem($(card), this);
                this.items.push(item);
            });
        }

        loadVotingScores() {
            if (this.isLoadingScores) return;  // Prevent multiple AJAX calls
            this.isLoadingScores = true;

            $.get(ajaxurl, {
                action: "get_voting_list_totals",
                voting_list_id: this.votingListId
            }, (response) => {
                this.isLoadingScores = false;  // Reset loading state

                if (response.success) {
                    let scores = response.data.items || [];
                    let totalScore = response.data.total || 0;

                    $(".total-list-score span").text(totalScore);

                    let $listContainer = $(".cs-vote-list");
                    let allItems = [];

                    console.log("✅ Response received:", scores);

                    // Ensure all `.voting-card` elements are considered
                    $(".voting-card").each(function () {
                        allItems.push({
                            element: $(this),
                            id: $(this).data("item-id"),
                            score: 0 // Default score if not found in response
                        });
                    });

                    // Match scores from AJAX response and update UI
                    allItems.forEach(item => {
                        let scoreData = scores.find(s => s.voting_item_id == item.id);
                        if (scoreData) {
                            item.score = Number(scoreData.total_points) || 0;
                            item.element.find(".total-points span").text(item.score);
                        }
                    });

                    // Sort items by score (highest first, 0-score last)
                    allItems.sort((a, b) => {
                        if (a.score === 0 && b.score > 0) return 1;
                        if (b.score === 0 && a.score > 0) return -1;
                        return b.score - a.score;
                    });

                    console.log("🔄 Sorted Items:", allItems.map(item => ({
                        id: item.id,
                        score: item.score
                    })));

                    // Clear & Reattach Items in Sorted Order
                    $listContainer.empty();
                    allItems.forEach((item, index) => {
                        item.element.find(".ranking").text(index + 1);
                        $listContainer.append(item.element);
                    });

                    console.log("✅ Items sorted and reattached");

                    // 🛠 **Fix:** Rebind Click Event Listeners
                    this.rebindVoteEvents();
                } else {
                    console.warn("❌ Error fetching scores:", response);
                }
            });
        }

        rebindVoteEvents() {
            console.log("🔄 Rebinding vote button click events...");
            this.items.forEach(item => item.initEvents());
        }

        getActiveVote(voteValue) {
            return this.items.find(item => item.$card.find(`.vote-btn.cs-vote-active[data-value="${voteValue}"]`).length);
        }

        submitVote(itemId, voteValue) {
            $.post(ajaxurl, {
                action: "submit_vote",
                voting_list_id: this.votingListId,
                voting_item_id: itemId,
                vote_value: voteValue
            }, () => {
                this.loadVotingScores();  // Refresh scores after voting
            });
        }

        removeVote(itemId, voteValue) {
            $.post(ajaxurl, {
                action: "remove_vote",
                voting_list_id: this.votingListId,
                voting_item_id: itemId,
                vote_value: voteValue
            }, () => {
                this.loadVotingScores();  // Refresh scores after removing vote
            });
        }

        loadUserVotes() {
            $.get(ajaxurl, {
                action: "get_user_votes",
                voting_list_id: this.votingListId
            }, (response) => {
                if (response.success) {
                    response.data.forEach(vote => {
                        let item = this.items.find(item => item.itemId == vote.voting_item_id);
                        if (item) {
                            item.loadVote(vote.vote_value);
                        }
                    });
                }
            });
        }
    }

    new VotingList();
    
  const popup = document.getElementById('cs-video-popup');
  const iframe = document.getElementById('cs-popup-iframe');
  const overlay = popup.querySelector('.cs-popup-overlay');
  const closeBtn = popup.querySelector('.cs-popup-close');

  document.querySelectorAll('.cs-play-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const videoUrl = btn.dataset.videoUrl;
      const embedUrl = convertToEmbed(videoUrl);
      iframe.src = embedUrl;
      popup.style.display = 'flex';
    });
  });

  [overlay, closeBtn].forEach(el => {
    el.addEventListener('click', () => {
      iframe.src = '';
      popup.style.display = 'none';
    });
  });

  function convertToEmbed(url) {
    if (url.includes('youtube.com/watch')) {
      const videoId = url.split('v=')[1].split('&')[0];
      return `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    } else if (url.includes('youtu.be/')) {
      const videoId = url.split('youtu.be/')[1];
      return `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    }
    return url;
  }
});

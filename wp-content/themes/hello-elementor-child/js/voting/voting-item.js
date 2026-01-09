(function($){
class VotingItem {
    constructor(id, $element, votingList) {
        this.id = parseInt(id, 10); 
        this.$element = $element;
        this.votingList = votingList;

        this.$voteButtons = this.$element.find('.cs-voting-card__option-button');
        this.$descriptionContainer = this.$element.find('.cs-voting-card__description'); 
        this.$scoreDisplay = this.$element.find('.cs-voting-card__score-value'); 
        this.$rankDisplay = this.$element.find('.cs-voting-card__rank'); 
        this.$playButton = this.$element.find('.cs-voting-card__play-button');

        this.initEvents();
    }

    initEvents() {
        this.$voteButtons.off('click.votingItem').on('click.votingItem', (event) => {
            const $button = $(event.currentTarget);
            const voteValue = parseInt($button.data('value'), 10);
            this.votingList.attemptToAssignVote(this.id, voteValue);
        });

        if (this.$playButton.length) {
            this.$playButton.off('click.votingItemVideo').on('click.votingItemVideo', (event) => {
                event.preventDefault();
                const videoUrl = this.$playButton.data('video-url');
                if (videoUrl) {
                    this.votingList.requestVideoPlayback(videoUrl);
                } else {
                    console.warn('Play button clicked, but no video URL found on item ID:', this.id);
                }
            });
        }
    }

    getTitle() {
        let title = this.$element.find('.cs-voting-card__title a').text().trim();
        if (!title) {
            title = this.$element.find('.cs-voting-card__title').text().trim();
        }
        return title || 'Predmet'; 
    }

    updateButtonStates(activeValueForThisItem, globallyAssignedVoteValuesMap) {
        const activeModifier = 'cs-voting-card__option-button--active';
        const disabledModifier = 'cs-voting-card__option-button--disabled'; 

        this.$voteButtons.each((_, btn) => {
            const $button = $(btn);
            const buttonValue = parseInt($button.data('value'), 10);

            // Reset states first
            $button.removeClass(activeModifier + ' ' + disabledModifier); 

            if (activeValueForThisItem === buttonValue) {
                $button.addClass(activeModifier);
            } else if (globallyAssignedVoteValuesMap[String(buttonValue)] && 
                       globallyAssignedVoteValuesMap[String(buttonValue)] !== this.id) {
                $button.addClass(disabledModifier);
            }
        });
    }
        
    
    updateScoreDisplay(newScore) {
        if (this.$scoreDisplay.length) {
            this.$scoreDisplay.text(parseInt(newScore, 10));
        }
    }

    updateRankDisplay(newRank) {
        if (this.$rankDisplay.length) {
            this.$rankDisplay.text(parseInt(newRank, 10));
        }
    }
}

    if (typeof window.VotingItem === 'undefined') {
        window.VotingItem = VotingItem;
    }
})(jQuery)

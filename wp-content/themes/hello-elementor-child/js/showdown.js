/**
 * Showdown — Client-side Game Engine
 * Matches mockup 01-arena.html animations exactly.
 * Winner stays in place. Loser exits with direction animation.
 * New challenger enters from the same direction.
 */
(function ($) {
    "use strict";

    const Showdown = {
        items: [],
        queue: [],
        eliminated: [],
        currentA: null,
        currentB: null,
        totalRounds: 0,
        currentRound: 0,
        showdownId: 0,
        locked: false,

        init: function () {
            const $page = $(".sd-page#yuv-showdown-arena");
            if (!$page.length) return;

            this.showdownId = parseInt($page.data("showdown-id"));
            const hasPlayed = $page.data("has-played") === 1 || $page.data("has-played") === "1";
            const status = $page.data("status");

            if (hasPlayed || status === "completed") {
                return;
            }

            try {
                const itemsAttr = $page.attr("data-items");
                this.items = JSON.parse(itemsAttr);
            } catch (e) {
                console.error("Failed to parse showdown items:", e);
                return;
            }

            if (this.items.length < 2) return;

            // Shuffle
            const shuffled = this.shuffleArray([...this.items]);
            this.currentA = shuffled[0];
            this.currentB = shuffled[1];
            this.queue = shuffled.slice(2);
            this.eliminated = [];
            this.totalRounds = this.items.length - 1;
            this.currentRound = 0;

            // Show progress
            $("#sd-progress").show();
            this.updateProgress();

            // Populate initial cards
            this.renderFighter("a", this.currentA);
            this.renderFighter("b", this.currentB);

            // Bind events
            this.bindPicks();
        },

        shuffleArray: function (arr) {
            for (let i = arr.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [arr[i], arr[j]] = [arr[j], arr[i]];
            }
            return arr;
        },

        renderFighter: function (slot, fighter) {
            const prefix = "#sd-fighter-" + slot;
            $(prefix + "-name").text(fighter.name);
            $(prefix + "-desc").text(fighter.description || "");
            if (fighter.image) {
                $(prefix + "-img")
                    .attr("src", fighter.image)
                    .attr("alt", fighter.name);
            }
        },

        bindPicks: function () {
            const self = this;

            // Button click
            $(document).on("click", ".sd-fighter__pick", function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (self.locked) return;
                const card = $(this).closest(".sd-fighter");
                const slot = card.data("side") === "left" ? "a" : "b";
                self.pickWinner(slot);
            });

            // Card click
            $(document).on("click", ".sd-fighter", function (e) {
                if ($(e.target).closest(".sd-fighter__pick").length) return;
                if (self.locked) return;
                const slot = $(this).data("side") === "left" ? "a" : "b";
                self.pickWinner(slot);
            });
        },

        pickWinner: function (winnerSlot) {
            if (this.locked) return;
            this.locked = true;

            const self = this;
            const loserSlot = winnerSlot === "a" ? "b" : "a";
            const winnerCard = document.getElementById("sd-fighter-" + winnerSlot);
            const loserCard = document.getElementById("sd-fighter-" + loserSlot);
            const arena = document.getElementById("sd-arena");
            const vs = document.getElementById("sd-vs");

            const winner = winnerSlot === "a" ? this.currentA : this.currentB;
            const loser = winnerSlot === "a" ? this.currentB : this.currentA;

            arena.classList.add("sd-arena--locked");
            this.currentRound++;
            this.eliminated.push(loser.name);

            // Winner pulse animation
            winnerCard.classList.add("anim-winner");
            vs.classList.add("anim-pulse");

            // Loser exit animation (direction-aware)
            const loserDir = loserSlot === "a" ? "anim-loser-left" : "anim-loser-right";
            loserCard.classList.add(loserDir);

            setTimeout(function () {
                if (self.queue.length === 0) {
                    // Game over — show results after a brief pause
                    setTimeout(function () {
                        self.showWinner(winner);
                    }, 400);
                    return;
                }

                const newChallenger = self.queue.shift();

                // Update tracking — winner stays in place
                if (loserSlot === "a") { self.currentA = newChallenger; }
                else { self.currentB = newChallenger; }
                if (winnerSlot === "a") { self.currentA = winner; }
                else { self.currentB = winner; }

                // Populate the loser card with new challenger
                self.renderFighter(loserSlot, newChallenger);

                // Remove loser exit, add enter animation
                loserCard.classList.remove(loserDir);
                const enterDir = loserSlot === "a" ? "anim-enter-left" : "anim-enter-right";
                loserCard.classList.add(enterDir);

                self.updateProgress();

                // Cleanup animations
                setTimeout(function () {
                    winnerCard.classList.remove("anim-winner");
                    loserCard.classList.remove(enterDir);
                    vs.classList.remove("anim-pulse");
                    arena.classList.remove("sd-arena--locked");
                    self.locked = false;
                }, 500);

            }, 500);
        },

        updateProgress: function () {
            const percent = this.totalRounds > 0
                ? Math.round((this.currentRound / this.totalRounds) * 100)
                : 0;
            const remaining = this.queue.length + 2;

            $("#sd-progress-fill").css("width", percent + "%");
            $("#sd-progress-round").text("Runda " + (this.currentRound + 1) + " od " + this.totalRounds);
            $("#sd-progress-remaining").text(Math.max(remaining, 2));
        },

        showWinner: function (winner) {
            const self = this;

            // Add winner as last (top) in elimination order
            this.eliminated.push(winner.name);

            // Fade out the arena page
            $(".sd-page").addClass("sd-page--fade-out");

            // Save to server
            $.ajax({
                url: yuvShowdown.ajaxurl,
                method: "POST",
                data: {
                    action: "yuv_showdown_save_session",
                    nonce: yuvShowdown.nonce,
                    showdown_id: self.showdownId,
                    results: JSON.stringify(self.eliminated),
                    winner: winner.name,
                },
                success: function (response) {
                    setTimeout(function () {
                        if (response.success) {
                            self.renderLeaderboard(
                                response.data.leaderboard,
                                response.data.total_players
                            );
                        } else {
                            self.renderLocalLeaderboard();
                        }
                    }, 500);
                },
                error: function () {
                    setTimeout(function () {
                        self.renderLocalLeaderboard();
                    }, 500);
                },
            });
        },

        showResults: function ($results, html) {
            $results.html('<div class="sd-results-inner">' + html + '</div>');
            setTimeout(function () {
                $(".sd-page").css("display", "none");
            }, 600);

            requestAnimationFrame(function () {
                $results.addClass("sd-results-screen--visible");
            });
        },

        renderLeaderboard: function (leaderboard, totalPlayers) {
            const $results = $("#sd-results");
            const title = $(".showdown-title").first().text();

            let html = '';
            html += '<header class="showdown-header sd-results-header">';
            html += '<div class="showdown-badge"><i class="ri-trophy-fill"></i> Rezultati</div>';
            html += '<h1 class="showdown-title">' + Showdown.escHtml(title) + '</h1>';
            html += '<p class="showdown-subtitle">Showdown završen — evo konačnog poretka</p>';
            html += '</header>';

            // Podium
            html += '<section class="sd-podium">';
            const podiumOrder = [
                { idx: 1, cls: 'silver' },
                { idx: 0, cls: 'gold' },
                { idx: 2, cls: 'bronze' }
            ];
            for (const p of podiumOrder) {
                if (!leaderboard[p.idx]) continue;
                const entry = leaderboard[p.idx];
                const winPct = totalPlayers > 0 ? ((entry.wins / totalPlayers) * 100).toFixed(1) : 0;
                
                html += '<div class="sd-podium__item sd-podium__item--' + p.cls + '">';
                html += '<div class="sd-podium__avatar-wrap">';
                if (p.cls === 'gold') {
                    html += '<div class="sd-podium__crown"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16H5Z" fill="currentColor"/></svg></div>';
                }
                html += '<div class="sd-podium__rank-badge">' + (p.idx + 1) + '</div>';
                if (entry.image) html += '<img class="sd-podium__avatar" src="' + entry.image + '" alt="' + Showdown.escHtml(entry.name) + '">';
                html += '<div class="sd-podium__pct-badge">' + winPct + '%</div>';
                html += '</div>';
                html += '<h3 class="sd-podium__name">' + Showdown.escHtml(entry.name) + '</h3>';
                html += '<p class="sd-podium__stats">' + entry.wins + ' pobeda</p>';
                html += '</div>';
            }
            html += '</section>';

            // Ranked list
            if (leaderboard.length > 3) {
                html += '<section class="sd-ranked">';
                html += '<h2 class="sd-ranked__title">Ostali plasmani</h2>';
                html += '<div class="sd-ranked__list">';
                for (let i = 3; i < leaderboard.length; i++) {
                    const entry = leaderboard[i];
                    const winPct = totalPlayers > 0 ? ((entry.wins / totalPlayers) * 100).toFixed(1) : 0;
                    const delay = 0.6 + (i - 3) * 0.08;
                    
                    html += '<div class="sd-ranked__item" style="transition-delay:' + delay + 's">';
                    html += '<span class="sd-ranked__rank">#' + (i + 1) + '</span>';
                    html += '<div class="sd-ranked__avatar-wrap">';
                    if (entry.image) html += '<img class="sd-ranked__avatar" src="' + entry.image + '" alt="">';
                    html += '</div>';
                    html += '<div class="sd-ranked__info">';
                    html += '<h4 class="sd-ranked__name">' + Showdown.escHtml(entry.name) + '</h4>';
                    if (entry.description) html += '<p class="sd-ranked__desc">' + Showdown.escHtml(entry.description) + '</p>';
                    html += '</div>';
                    html += '<div class="sd-ranked__score">';
                    html += '<span class="sd-ranked__pct">' + winPct + '%</span>';
                    html += '<span class="sd-ranked__wins">' + entry.wins + ' pobeda</span>';
                    html += '</div>';
                    html += '</div>';
                }
                html += '</div></section>';
            }

            this.showResults($results, html);
        },

        renderLocalLeaderboard: function () {
            const $results = $("#sd-results");
            const title = $(".showdown-title").first().text();
            const ranked = [...Showdown.eliminated].reverse();

            let html = '';
            html += '<header class="showdown-header sd-results-header">';
            html += '<div class="showdown-badge"><i class="ri-trophy-fill"></i> Tvoji Rezultati</div>';
            html += '<h1 class="showdown-title">' + Showdown.escHtml(title) + '</h1>';
            html += '<p class="showdown-subtitle">Evo tvog ličnog poretka</p>';
            html += '</header>';

            // Podium
            html += '<section class="sd-podium">';
            const podiumOrder = [
                { idx: 1, cls: 'silver' },
                { idx: 0, cls: 'gold' },
                { idx: 2, cls: 'bronze' }
            ];
            for (const p of podiumOrder) {
                if (!ranked[p.idx]) continue;
                const name = ranked[p.idx];
                const item = Showdown.items.find(function (it) { return it.name === name; });
                
                html += '<div class="sd-podium__item sd-podium__item--' + p.cls + '">';
                html += '<div class="sd-podium__avatar-wrap">';
                if (p.cls === 'gold') {
                    html += '<div class="sd-podium__crown"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16H5Z" fill="currentColor"/></svg></div>';
                }
                html += '<div class="sd-podium__rank-badge">' + (p.idx + 1) + '</div>';
                if (item && item.image) {
                    html += '<img class="sd-podium__avatar" src="' + item.image + '" alt="' + name + '">';
                }
                html += '</div>';
                html += '<h3 class="sd-podium__name">' + Showdown.escHtml(name) + '</h3>';
                html += '</div>';
            }
            html += '</section>';

            // Ranked list
            if (ranked.length > 3) {
                html += '<section class="sd-ranked">';
                html += '<h2 class="sd-ranked__title">Ostali plasmani</h2>';
                html += '<div class="sd-ranked__list">';
                for (let i = 3; i < ranked.length; i++) {
                    const name = ranked[i];
                    const item = Showdown.items.find(function (it) { return it.name === name; });
                    const delay = 0.6 + (i - 3) * 0.08;
                    
                    html += '<div class="sd-ranked__item" style="transition-delay:' + delay + 's">';
                    html += '<span class="sd-ranked__rank">#' + (i + 1) + '</span>';
                    html += '<div class="sd-ranked__avatar-wrap">';
                    if (item && item.image) {
                        html += '<img class="sd-ranked__avatar" src="' + item.image + '" alt="">';
                    }
                    html += '</div>';
                    html += '<div class="sd-ranked__info">';
                    html += '<h4 class="sd-ranked__name">' + Showdown.escHtml(name) + '</h4>';
                    html += '</div>';
                    html += '</div>';
                }
                html += '</div></section>';
            }

            this.showResults($results, html);
        },

        escHtml: function (str) {
            if (!str) return "";
            return $("<div>").text(str).html();
        },
    };

    $(document).ready(function () {
        Showdown.init();
    });
})(jQuery);

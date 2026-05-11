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

            // Always check via AJAX to bypass LiteSpeed cache and get real has_played state
            this.checkStatusAndInit($page);
        },

        checkStatusAndInit: function ($page) {
            const self = this;

            $.ajax({
                url: yuvShowdown.ajaxurl,
                method: "POST",
                data: {
                    action: "yuv_showdown_get_items",
                    nonce: yuvShowdown.nonce,
                    showdown_id: self.showdownId,
                },
                success: function (response) {
                    if (response.success && response.data.has_played) {
                        // User has played, show results immediately
                        self.items = response.data.items;
                        self.renderLeaderboard(
                            response.data.leaderboard,
                            response.data.total_players
                        );
                    } else {
                        // User hasn't played, initialize the arena
                        self.startArena($page);
                    }
                },
                error: function () {
                    // Fallback to local data if AJAX fails
                    self.startArena($page);
                }
            });
        },

        startArena: function ($page) {
            const hasPlayedServer = $page.data("has-played") === 1 || $page.data("has-played") === "1";
            if (hasPlayedServer) {
                // If even the server knows we played, and AJAX didn't catch it (rare), 
                // we might want to hide, but usually we just start the arena if AJAX said OK.
            }

            try {
                this.items = JSON.parse($page.attr("data-items"));
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
            this.bindResults();
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
            if (fighter.image_url || fighter.image) {
                $(prefix + "-img")
                    .attr("src", fighter.image_url || fighter.image)
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

        bindResults: function () {
            // no-op: expand button removed, full results linked to showdown page
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
                // We set opacity directly to 0 briefly to prevent the "flash" of old content
                // as the class swaps from exit to enter
                loserCard.style.opacity = "0";

                setTimeout(function () {
                    self.renderFighter(loserSlot, newChallenger);
                    self.updateProgress();

                    loserCard.classList.remove(loserDir);
                    const enterDir = loserSlot === "a" ? "anim-enter-left" : "anim-enter-right";
                    loserCard.classList.add(enterDir);

                    // Cleanup animations
                    setTimeout(function () {
                        winnerCard.classList.remove("anim-winner");
                        loserCard.classList.remove(enterDir);
                        loserCard.style.opacity = ""; // Restore
                        vs.classList.remove("anim-pulse");
                        arena.classList.remove("sd-arena--locked");
                        self.locked = false;
                    }, 500);
                }, 50);

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

        /**
         * Show results as a fixed overlay (light theme) with staggered animations
         */
        showResults: function ($results, html) {
            // Wrap in inner container
            $results.html('<div class="sd-results-inner">' + html + '</div>');

            // Hide the arena page completely after fade-out completes
            setTimeout(function () {
                $(".sd-page").css("display", "none");
            }, 600);

            // Trigger the results to fade in
            requestAnimationFrame(function () {
                $results.addClass("sd-results-screen--visible");
            });
        },

        renderLeaderboard: function (leaderboard, totalPlayers) {
            const $results = $("#sd-results");
            const title = $(".showdown-title").first().text();

            let html = '';
            html += '<header class="showdown-header sd-results-header">';
            html += '<div class="showdown-badge"><i class="ri-sword-line"></i> Može biti samo jedan</div>';
            html += '<h1 class="showdown-title">' + Showdown.escHtml(title) + '</h1>';
            html += '<p class="showdown-subtitle">Trenutni poredak</p>';
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
                html += '<div class="sd-podium__item sd-podium__item--' + p.cls + '">';
                html += '<div class="sd-podium__avatar-wrap">';
                if (p.cls === 'gold') html += '<span class="sd-podium__crown">👑</span>';
                html += '<span class="sd-podium__medal">' + (p.idx + 1) + '</span>';
                if (entry.image) html += '<img class="sd-podium__avatar" src="' + entry.image + '" alt="' + Showdown.escHtml(entry.name) + '">';
                html += '</div>';
                html += '<h3 class="sd-podium__name">' + Showdown.escHtml(entry.name) + '</h3>';

                const winRate = entry.sessions > 0 ? Math.round((entry.wins / entry.sessions) * 100) : 0;
                html += '<p class="sd-podium__wins">Pobede: <strong>' + winRate + '%</strong></p>';
                html += '</div>';
            }
            html += '</section>';

            // Full list on own page, redirect button elsewhere
            const showdownUrl = $(".sd-page#yuv-showdown-arena").data("showdown-url");
            const normalize = function (u) { return u.replace(/\/+$/, ''); };
            const isOwnPage = showdownUrl && normalize(window.location.href) === normalize(showdownUrl);

            if (isOwnPage && leaderboard.length > 3) {
                html += '<section class="sd-ranked">';
                html += '<div class="sd-ranked__list">';
                for (let i = 3; i < leaderboard.length; i++) {
                    const entry = leaderboard[i];
                    const delay = 0.1 + (i - 3) * 0.05;
                    const winRate = entry.sessions > 0 ? Math.round((entry.wins / entry.sessions) * 100) : 0;
                    html += '<div class="sd-ranked__item" style="transition-delay:' + delay + 's">';
                    html += '<span class="sd-ranked__rank">' + (i + 1) + '</span>';
                    if (entry.image) html += '<img class="sd-ranked__avatar" src="' + entry.image + '" alt="">';
                    html += '<div class="sd-ranked__info">';
                    html += '<h4 class="sd-ranked__name">' + Showdown.escHtml(entry.name) + '</h4>';
                    html += '</div>';
                    html += '<span class="sd-ranked__stats">Pobede: <strong>' + winRate + '%</strong></span>';
                    html += '</div>';
                }
                html += '</div></section>';
            } else if (!isOwnPage && showdownUrl) {
                html += '<section class="sd-ranked">';
                html += '<div class="sd-ranked__expand">';
                html += '<a href="' + Showdown.escHtml(showdownUrl) + '" class="sd-btn--cta">';
                html += '<i class="ri-bar-chart-2-line"></i> Pogledaj kompletan poredak';
                html += '</a>';
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
            html += '<div class="showdown-badge"><i class="ri-sword-line"></i> Može biti samo jedan</div>';
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
                if (p.cls === 'gold') html += '<span class="sd-podium__crown">👑</span>';
                html += '<span class="sd-podium__medal">' + (p.idx + 1) + '</span>';
                if (item && (item.image_url || item.image)) {
                    html += '<img class="sd-podium__avatar" src="' + (item.image_url || item.image) + '" alt="' + name + '">';
                }
                html += '</div>';
                html += '<h3 class="sd-podium__name">' + Showdown.escHtml(name) + '</h3>';
                html += '</div>';
            }
            html += '</section>';

            // Full list on own page, redirect button elsewhere
            const localShowdownUrl = $(".sd-page#yuv-showdown-arena").data("showdown-url");
            const normalizeLocal = function (u) { return u.replace(/\/+$/, ''); };
            const isOwnPageLocal = localShowdownUrl && normalizeLocal(window.location.href) === normalizeLocal(localShowdownUrl);

            if (isOwnPageLocal && ranked.length > 3) {
                html += '<section class="sd-ranked">';
                html += '<div class="sd-ranked__list">';
                for (let i = 3; i < ranked.length; i++) {
                    const name = ranked[i];
                    const item = Showdown.items.find(function (it) { return it.name === name; });
                    const delay = 0.6 + (i - 3) * 0.08;
                    html += '<div class="sd-ranked__item" style="transition-delay:' + delay + 's">';
                    html += '<span class="sd-ranked__rank">' + (i + 1) + '</span>';
                    if (item && (item.image_url || item.image)) {
                        html += '<img class="sd-ranked__avatar" src="' + (item.image_url || item.image) + '" alt="">';
                    }
                    html += '<div class="sd-ranked__info">';
                    html += '<h4 class="sd-ranked__name">' + Showdown.escHtml(name) + '</h4>';
                    html += '</div>';
                    html += '</div>';
                }
                html += '</div></section>';
            } else if (!isOwnPageLocal && localShowdownUrl) {
                html += '<section class="sd-ranked">';
                html += '<div class="sd-ranked__expand">';
                html += '<a href="' + Showdown.escHtml(localShowdownUrl) + '" class="sd-btn--cta">';
                html += '<i class="ri-bar-chart-2-line"></i> Pogledaj kompletan poredak';
                html += '</a>';
                html += '</div></section>';
            }

            this.showResults($results, html);
        },

        escHtml: function (str) {
            if (!str) return "";
            return $("<div>").text($("<div>").html(str).text()).html();
        },
    };

    $(document).ready(function () {
        Showdown.init();
    });
})(jQuery);

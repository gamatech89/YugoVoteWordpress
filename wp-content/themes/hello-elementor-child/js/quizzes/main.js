async function api(url, opts = {}) {
  const res = await fetch(url, {
    method: opts.method || "GET",
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": quizSettings.nonce,
    },
    credentials: "same-origin",
    body: opts.body ? JSON.stringify(opts.body) : undefined,
  });
  let data = {};
  try {
    data = await res.json();
  } catch (e) {}
  if (!res.ok)
    throw new Error((data && (data.message || data.code)) || "Greška.");
  return data;
}

const Templates = {
  introScreen: (quiz) => `
    <div class="ygv-quiz-card fade-in">
      ${
        quiz.featured_image
          ? `
        <div class="ygv-quiz-header" style="background-image: url('${quiz.featured_image}');">
          <span class="ygv-quiz-badge" style="background-color: var(--quiz-primary-color);">
            ${quiz.category_name}
          </span>
        </div>
      `
          : `
        <div class="ygv-quiz-header">
          <span class="ygv-quiz-badge" style="background-color: var(--quiz-primary-color);">
            ${quiz.category_name}
          </span>
        </div>
      `
      }
      
      <div class="ygv-quiz-body">
        <h2 class="ygv-quiz-title">${quiz.title}</h2>
        <p class="ygv-quiz-description">${quiz.description}</p>
        
        <div class="ygv-quiz-meta">
          <div class="ygv-quiz-meta-item">
            <i class="ri-file-list-3-line"></i>
            <span>${quiz.num_questions} pitanja</span>
          </div>
          <div class="ygv-quiz-meta-item">
            <i class="ri-time-line"></i>
            <span>${quiz.time_per_question}s po pitanju</span>
          </div>
          ${
            quiz.quiz_difficulty
              ? `
            <div class="ygv-quiz-meta-item">
              <i class="ri-star-line"></i>
              <span>${quiz.quiz_difficulty}</span>
            </div>
          `
              : ""
          }
        </div>
        
        <button id="start-btn" class="ygv-btn-primary" style="background-color: var(--quiz-primary-color);">
          Započni Kviz
          <i class="ri-arrow-right-line"></i>
        </button>
      </div>
    </div>
  `,

  questionScreen: (question, current, total, timeLeft) => `
    <div class="ygv-quiz-question fade-in">
      <div class="ygv-quiz-progress">
        <div class="ygv-quiz-progress-bar" style="width: ${
          (current / total) * 100
        }%; background-color: var(--quiz-primary-color);"></div>
      </div>
      
      <div class="ygv-quiz-timer">
        <svg class="ygv-timer-ring" width="80" height="80">
          <circle class="ygv-timer-ring-bg" cx="40" cy="40" r="36"></circle>
          <circle class="ygv-timer-ring-progress" cx="40" cy="40" r="36" style="stroke: var(--quiz-primary-color);"></circle>
        </svg>
        <span class="ygv-timer-text" id="timer">${timeLeft}</span>
      </div>
      
      <p class="ygv-question-counter">Pitanje ${current} od ${total}</p>
      <h3 class="ygv-question-title">${question.title}</h3>
      
      <div id="answers" class="ygv-answers-grid"></div>
    </div>
  `,

  waitScreen: (step, total, timeLeft = 3) => `
    <div class="ygv-wait-screen fade-in">
      <h3>Sledeće pitanje</h3>
      <div class="ygv-countdown-circle">
        <svg width="120" height="120">
          <circle class="ygv-countdown-bg" cx="60" cy="60" r="54"></circle>
          <circle class="ygv-countdown-progress" cx="60" cy="60" r="54" style="stroke: var(--quiz-primary-color);"></circle>
        </svg>
        <span class="ygv-countdown-text" id="countdownTimer">${timeLeft}</span>
      </div>
      <p>Pitanje ${step} od ${total}</p>
    </div>
  `,

  summaryScreen: (score, total, isGuest = false) => {
    const percent = (score / total) * 100;
    let icon, iconClass, title;

    if (percent >= 70) {
      icon = "ri-trophy-line";
      iconClass = "success";
      title = "Odličan rezultat!";
    } else if (percent >= 40) {
      icon = "ri-thumb-up-line";
      iconClass = "neutral";
      title = "Dobar pokušaj!";
    } else {
      icon = "ri-emotion-unhappy-line";
      iconClass = "fail";
      title = "Probaj ponovo!";
    }

    return `
    <div class="ygv-quiz-summary fade-in">
      <div class="ygv-summary-icon">
        <i class="${icon} yuv-result-icon ${iconClass}"></i>
      </div>
      <h2>${title}</h2>
      <div class="ygv-summary-score">
        <span class="ygv-score-number">${score}</span>
        <span class="ygv-score-divider">/</span>
        <span class="ygv-score-total">${total}</span>
      </div>
      <p class="ygv-summary-percent">${Math.round(percent)}% tačnih odgovora</p>
      
      ${
        isGuest
          ? `
        <div class="ygv-guest-cta">
          <p class="ygv-guest-cta-text">Želiš još kvizova i čuvanje rezultata?</p>
          <a href="/registracija" class="ygv-guest-cta-btn">Kreiraj Nalog Besplatno</a>
        </div>
      `
          : ""
      }
      
      <button class="ygv-btn-primary" onclick="window.location.reload()" style="background-color: var(--quiz-primary-color); margin-top: 20px;">
        Pokušaj ponovo
      </button>
    </div>
  `;
  },

  loginPrompt: () => `
    <div class="ygv-login-prompt fade-in">
      <div class="ygv-login-icon">
        <i class="ri-lock-2-line yuv-result-icon" style="color: var(--quiz-primary-color);"></i>
      </div>
      <h2>Za ovaj kviz je potrebna prijava</h2>
      <p>Prijavite se da biste igrali i osvajali bodove.</p>
      <div class="ygv-login-buttons">
        <a href="/login" class="ygv-btn-primary" style="background-color: var(--quiz-primary-color);">
          Prijavi se
        </a>
        <a href="/registracija" class="ygv-btn-secondary">
          Registruj se
        </a>
      </div>
    </div>
  `,
};

class Quiz {
  constructor(apiUrl, quizId) {
    this.apiUrl = apiUrl;
    this.quizId = quizId;
    this.quizData = null;
    this.currentStep = 0;
    this.userAnswers = [];
    this.score = 0;
    this.timePerQuestion = 10;
    this.attemptId = null;
    this.categoryColor = "#6A0DAD";
    this.allowGuestPlay = false;
    this.isGuest = false;

    this.setupQuizContainer();
    this.controller = new QuizController(this);
    this.fetchQuizData();
  }

  async startAttempt() {
    try {
      const r = await api(`${this.apiUrl}/quiz/${this.quizId}/start`, {
        method: "POST",
      });
      this.attemptId = r.attempt_id;
      this.isGuest = r.is_guest || false;
      window.dispatchEvent(new CustomEvent("ygv:state:changed"));
      return r;
    } catch (error) {
      // Check for guest access restriction errors
      if (
        error.message &&
        (error.message.includes("prijavljeni") ||
          error.message.includes("login") ||
          error.message.includes("prijavite") ||
          error.message.includes("Morate biti") ||
          error.message.includes("restricted") ||
          error.message.includes("guests"))
      ) {
        this.controller.showLoginPrompt();
        throw error;
      }
      throw error;
    }
  }

  async submitAttempt() {
    if (!this.attemptId) return;
    const body = {
      attempt_id: this.attemptId,
      correct: this.score,
      total: this.quizData.length,
    };
    const r = await api(`${this.apiUrl}/quiz/${this.quizId}/submit`, {
      method: "POST",
      body,
    });
    if (r.is_guest) {
      console.log("Guest result:", r.message);
    }
    window.dispatchEvent(new CustomEvent("ygv:state:changed"));
    return r;
  }

  setupQuizContainer() {
    if (!document.querySelector(".cs-quiz-wrapper")) {
      document.body.insertAdjacentHTML(
        "beforeend",
        `<div class="cs-quiz">
                    <div class="cs-quiz-wrapper">
                        <div class="cs-quiz-topbar">
                            <div class="cs-step-counter" id="quiz-counter"></div>
                             <div class="cs-controlls">
                              <button class="cs-close-button"><i class="ri-close-line"></i></button>
                             </div>
                        </div>
                        <div class="cs-quiz-container"></div>
                    </div>
                </div>`
      );
    }

    this.wrapper = document.querySelector(".cs-quiz-wrapper");
    this.topBar = this.wrapper.querySelector(".cs-quiz-topbar");
    this.controllsBar = this.wrapper.querySelector(".cs-controlls");
    this.counter = this.wrapper.querySelector("#quiz-counter");
    this.container = this.wrapper.querySelector(".cs-quiz-container");

    // Attach close event listener
    this.closeButton = this.wrapper.querySelector(".cs-close-button");
    this.closeButton.addEventListener("click", () => this.closeQuiz());
  }

  closeQuiz() {
    clearInterval(this.controller.timer);

    const quizElement = document.querySelector(".cs-quiz");
    if (quizElement) {
      quizElement.remove();
    }
    this.quizData = null;
    this.currentStep = 0;
    this.userAnswers = [];
    this.score = 0;
  }

  updateQuestionCounter() {
    if (this.counter) {
      this.counter.innerText = `${this.currentStep + 1} / ${
        this.quizData.length
      }`;
    }
  }

  async fetchQuizData() {
    try {
      const response = await fetch(`${this.apiUrl}/quiz/${this.quizId}`);
      const data = await response.json();

      if (!data.success || !data.questions?.length) {
        throw new Error("No questions found.");
      }

      this.quizTitle = data.title;
      this.quizDescription = data.description;
      this.quizImage = data.featured_image;
      this.quizData = data.questions;
      this.quizDifficulty = data.quiz_difficulty;
      this.categoryColor = data.category_color || "#6A0DAD";
      this.categoryName = data.category_name || "Opšte";
      this.timePerQuestion = data.time_per_question || 10;
      this.allowGuestPlay = data.allow_guest_play || false;

      // Inject color as CSS variable
      this.wrapper.style.setProperty(
        "--quiz-primary-color",
        this.categoryColor
      );

      this.updateQuestionCounter();
      this.controller.showIntroScreen();
    } catch (error) {
      this.controller.showError("Failed to load quiz.");
    }
  }
}
class QuizController {
  constructor(quiz) {
    this.quiz = quiz;
    this.container = quiz.container;
    this.isMuted = localStorage.getItem("quizMuted") === "true";

    this.sounds = {
      correct: this.loadSound("correct.mp3"),
      wrong: this.loadSound("wrong.mp3"),
      background: this.loadSound("background.mp3", true, 0.2),
    };

    this.createMuteButton();
  }

  showError(message) {
    console.error("Quiz Error:", message); // Good to log the error too

    // Display the error message within the quiz container
    this.container.innerHTML = `
            <div class="cs-quiz-error fade-in">
                <h2>Greška!</h2> 
                <p>${message}</p>
                <p>Molimo pokušajte ponovo kasnije.</p> 
                <button class="cs-main-button" onclick="window.location.reload()">Osvežite Stranicu</button> 
                
            </div>
        `;
    // You might want to hide other UI elements like the counter if an error occurs
    if (this.quiz && this.quiz.counter) {
      this.quiz.counter.classList.remove("cs-show");
    }
    // Stop background sound if playing
    if (this.sounds && this.sounds.background) {
      this.sounds.background.pause();
      this.sounds.background.currentTime = 0; // Reset playback position
    }
  }

  loadSound(fileName, loop = false, volume = 1.0) {
    const sound = new Audio(quizSettings.soundPath + fileName);
    sound.loop = loop;
    sound.volume = volume;
    sound.muted = this.isMuted;
    return sound;
  }

  createMuteButton() {
    const muteButton = document.createElement("button");
    muteButton.innerHTML = this.isMuted
      ? '<i class="ri-volume-mute-line"></i>'
      : '<i class="ri-volume-up-line"></i>';
    muteButton.classList.add("cs-mute-button");
    muteButton.onclick = () => this.toggleMute(muteButton);

    this.quiz.controllsBar.appendChild(muteButton);
  }

  toggleMute(button) {
    this.isMuted = !this.isMuted;
    localStorage.setItem("quizMuted", this.isMuted);
    button.innerHTML = this.isMuted
      ? '<i class="ri-volume-mute-line"></i>'
      : '<i class="ri-volume-up-line"></i>';
    Object.values(this.sounds).forEach((sound) => (sound.muted = this.isMuted));
  }

  showIntroScreen() {
    this.container.innerHTML = Templates.introScreen({
      title: this.quiz.quizTitle,
      description: this.quiz.quizDescription,
      featured_image: this.quiz.quizImage,
      category_name: this.quiz.categoryName,
      num_questions: this.quiz.quizData.length,
      time_per_question: this.quiz.timePerQuestion,
      quiz_difficulty: this.quiz.quizDifficulty,
    });

    document.getElementById("start-btn").addEventListener("click", async () => {
      try {
        await this.quiz.startAttempt();
        this.startQuiz();
      } catch (e) {
        // Check if it's a login/access error - if so, login prompt is already shown
        const isLoginError =
          e.message &&
          (e.message.includes("prijavljeni") ||
            e.message.includes("login") ||
            e.message.includes("prijavite") ||
            e.message.includes("Morate biti") ||
            e.message.includes("restricted") ||
            e.message.includes("guests"));

        if (!isLoginError) {
          this.showError(e.message || "Neuspešno pokretanje kviza.");
        }
      }
    });
  }

  showLoginPrompt() {
    this.container.innerHTML = Templates.loginPrompt();
  }

  startQuiz() {
    if (!this.isMuted) this.sounds.background.play();
    this.quiz.updateQuestionCounter();

    this.quiz.counter.classList.add("cs-show");

    this.showQuestion();
  }

  showQuestion() {
    this.quiz.updateQuestionCounter();
    const question = this.quiz.quizData[this.quiz.currentStep];
    this.container.innerHTML = Templates.questionScreen(
      question,
      this.quiz.currentStep + 1,
      this.quiz.quizData.length,
      this.quiz.timePerQuestion
    );

    const answersContainer = document.getElementById("answers");
    question.answers.forEach((answer) => {
      const button = document.createElement("button");
      button.innerText = answer;
      button.classList.add("cs-answer-btn");
      button.addEventListener("click", () => this.checkAnswer(button, answer));
      answersContainer.appendChild(button);
    });

    this.startTimer();
  }

  startTimer() {
    clearInterval(this.timer);
    const timerDisplay = document.getElementById("timer");
    const progressRing = document.querySelector(".ygv-timer-ring-progress");
    let timeLeft = this.quiz.timePerQuestion;
    const maxTime = this.quiz.timePerQuestion;

    const radius = 36,
      circumference = 2 * Math.PI * radius;
    if (progressRing) {
      progressRing.style.strokeDasharray = `${circumference}`;
      progressRing.style.strokeDashoffset = 0;
    }

    this.timer = setInterval(() => {
      timeLeft--;
      if (timerDisplay) timerDisplay.innerText = timeLeft;
      if (progressRing) {
        progressRing.style.strokeDashoffset =
          ((maxTime - timeLeft) / maxTime) * circumference;
      }
      if (timeLeft <= 0) {
        clearInterval(this.timer);
        this.markTimeoutAsWrong();
      }
    }, 1000);
  }

  disableAllButtons() {
    document
      .querySelectorAll(".cs-answer-btn")
      .forEach((btn) => (btn.disabled = true));
  }

  highlightCorrectAnswer(correctAnswer) {
    document.querySelectorAll(".cs-answer-btn").forEach((btn) => {
      if (btn.innerText === correctAnswer) btn.classList.add("cs-correct");
    });
  }

  markTimeoutAsWrong() {
    const question = this.quiz.quizData[this.quiz.currentStep];
    this.disableAllButtons();
    this.highlightCorrectAnswer(question.answers[question.correct]);

    this.sounds.wrong.play();
    this.quiz.userAnswers.push({
      question: question.title,
      selected: "(No Answer)",
      correct: question.answers[question.correct],
      isCorrect: false,
    });

    setTimeout(() => this.nextQuestion(), 2000);
  }

  checkAnswer(button, selectedAnswer) {
    clearInterval(this.timer);
    const question = this.quiz.quizData[this.quiz.currentStep];

    // ✅ Fix: Ensure we always compare against the actual correct answer
    const correctAnswer = question.answers[question.correct];
    const isCorrect = selectedAnswer === correctAnswer;

    this.disableAllButtons();

    if (isCorrect) {
      button.classList.add("cs-correct");
      this.sounds.correct.play();
      this.quiz.score++;
    } else {
      button.classList.add("cs-wrong");
      this.sounds.wrong.play();
      this.highlightCorrectAnswer(correctAnswer); // ✅ Now highlights the actual correct answer
    }

    this.quiz.userAnswers.push({
      question: question.title,
      selected: selectedAnswer,
      correct: correctAnswer, // ✅ Ensure correct answer is properly stored
      isCorrect,
    });

    setTimeout(() => this.nextQuestion(), 1500);
  }

  nextQuestion() {
    this.quiz.currentStep++;

    if (this.quiz.currentStep < this.quiz.quizData.length) {
      this.showWaitingScreen();
    } else {
      this.showSummary();
    }
  }

  showWaitingScreen() {
    this.quiz.updateQuestionCounter();
    let countdownTime = 3;
    this.container.innerHTML = Templates.waitScreen(
      this.quiz.currentStep + 1,
      this.quiz.quizData.length,
      countdownTime
    );

    const countdownDisplay = document.getElementById("countdownTimer");
    const progressRing = document.querySelector(".ygv-countdown-progress");
    const maxTime = countdownTime;
    const circumference = 2 * Math.PI * 54;

    progressRing.style.strokeDasharray = circumference;
    progressRing.style.strokeDashoffset = 0;

    let countdownTimer = setInterval(() => {
      countdownTime--;
      countdownDisplay.innerText = countdownTime;

      // ✅ Update progress ring (keeps shrinking)
      progressRing.style.strokeDashoffset =
        ((maxTime - countdownTime) / maxTime) * circumference;

      if (countdownTime <= 0) {
        clearInterval(countdownTimer);
        this.showQuestion(); // ✅ Move to next question after countdown
      }
    }, 1000);
  }

  async showSummary() {
    try {
      await this.quiz.submitAttempt();
    } catch (e) {
      console.error(e);
    }
    this.container.innerHTML = Templates.summaryScreen(
      this.quiz.score,
      this.quiz.quizData.length,
      this.quiz.isGuest
    );
    this.sounds.background.pause();
  }
}

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".cs-quiz-card").forEach((card) => {
    const btnWrap = card.querySelector(".cs-quiz-button");
    if (!btnWrap) return;

    const anchor = btnWrap.querySelector("a") || btnWrap;

    // Prefer data-quiz-id on the card if you can add it in Elementor (Advanced → Attributes → data-quiz-id = {{ID}})
    const idFromData = card.dataset.quizId;
    const idFromHidden = card.querySelector(".cs-quiz-id")?.textContent?.trim();
    const idFromClass = (card.className.match(/post-(\d+)/) || [])[1];
    const quizId = idFromData || idFromHidden || idFromClass;

    if (!quizId) {
      console.error("Quiz ID not found for card:", card);
      return;
    }

    anchor.addEventListener("click", (e) => {
      e.preventDefault(); // prevent href="#" jump
      if (anchor.classList.contains("is-busy")) return; // guard double click
      anchor.classList.add("is-busy");

      // close any running quiz instance
      if (window.currentQuiz?.closeQuiz) window.currentQuiz.closeQuiz();

      window.currentQuiz = new Quiz(quizSettings.apiUrl, quizId);

      setTimeout(() => anchor.classList.remove("is-busy"), 800);
    });
  });
});

// ✅ Expose Quiz class globally for quiz grid and other integrations
window.Quiz = Quiz;

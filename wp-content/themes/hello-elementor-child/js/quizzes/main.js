async function api(url, opts = {}) {
  const res = await fetch(url, {
    method: opts.method || 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': quizSettings.nonce
    },
    credentials: 'same-origin',
    body: opts.body ? JSON.stringify(opts.body) : undefined
  });
  let data = {};
  try { data = await res.json(); } catch(e) {}
  if (!res.ok) throw new Error(data && (data.message || data.code) || 'Greška.');
  return data;
}

const Templates = {
  introScreen: (title, description, imageUrl) => `
    <div class="fade-in cs-intro-container">
      ${imageUrl ? `<img src="${imageUrl}" alt="${title}" class="quiz-image">` : ''}
      <h2>${title}</h2>
      <p>${description}</p>
      <button id="start-btn" class="cs-main-button">
        Zapocni Kviz
        <svg xmlns="http://www.w3.org/2000/svg" width="27" height="20" viewBox="0 0 27 20" fill="none">
          <path d="M1.5 9.81774C6.04008 10.4649 16.322 11.371 21.1292 9.81774C27.1381 7.87618 19.1262 6.48935 
          14.7196 2.8836C10.3131 -0.72216 27.3384 7.7375 25.3354 9.81774C23.3324 11.898 19.1262 16.3358 
          16.9229 18" stroke="#4457A5" stroke-width="3" stroke-linecap="round"></path>
        </svg>
      </button>
    </div>
  `,

  questionScreen: (question, step, total, timeLeft) => `
    <div class="fade-in scale-in cs-quiz-step">
      <div class="cs-timer-container">
        <svg class="cs-progress-ring" width="120" height="120">
          <circle class="cs-progress-ring-circle" cx="60" cy="60" r="55"></circle>
          <circle class="cs-progress-ring-fill"   cx="60" cy="60" r="55"></circle>
        </svg>
        <span class="cs-timer-text" id="timer">${timeLeft}</span>
      </div>
      <p class="cs-question-title">${question.title}</p>
      <div id="answers" class="cs-answers-container"></div>
    </div>
  `,

  nextQuestionScreen: (step, total, timeLeft = 3) => `
    <div class="fade-in scale-in cs-wait-screen">
      <h3>Pripremite se za sledece pitanje!</h3>
      <p>Pitanje ${step} krece za:</p>
      <div class="cs-timer-container">
        <svg class="cs-progress-ring" width="120" height="120">
          <circle class="cs-progress-ring-circle" cx="60" cy="60" r="55"></circle>
          <circle class="cs-progress-ring-fill cs-purple" cx="60" cy="60" r="55"></circle>
        </svg>
        <span class="cs-timer-text" id="countdownTimer">${timeLeft}</span>
      </div>
    </div>
  `,

  summaryScreen: (score, total) => `
    <div class="fade-in cs-end-screen">
      <h2>Kviz je Zavrsen!</h2>
      <p>Vas Skor Je: <strong>${score} / ${total}</strong></p>
      <button class="cs-main-button" onclick="window.location.reload()">Pokusaj opet</button>
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

    this.setupQuizContainer();
    this.controller = new QuizController(this);
    this.fetchQuizData();
  }

  async startAttempt() {
    const r = await api(`${this.apiUrl}/quiz/${this.quizId}/start`, { method: 'POST' });
    this.attemptId = r.attempt_id;
    // notify account panel to refresh tokens (optional)
    window.dispatchEvent(new CustomEvent('ygv:state:changed'));
  }

  async submitAttempt() {
    if (!this.attemptId) return;
    const body = { attempt_id: this.attemptId, correct: this.score, total: this.quizData.length };
    await api(`${this.apiUrl}/quiz/${this.quizId}/submit`, { method: 'POST', body });
    // refresh account panel (levels/tokens may change)
    window.dispatchEvent(new CustomEvent('ygv:state:changed'));
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
                              <button class="cs-close-button">❌</button>
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
            this.counter.innerText = `${this.currentStep + 1} / ${this.quizData.length}`;
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
            this.timePerQuestion = data.time_per_question || 10;

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
        muteButton.innerText = this.isMuted ? "🔇" : "🔊";
        muteButton.classList.add("cs-mute-button");
        muteButton.onclick = () => this.toggleMute(muteButton);

        this.quiz.controllsBar.appendChild(muteButton);
    }

    toggleMute(button) {
        this.isMuted = !this.isMuted;
        localStorage.setItem("quizMuted", this.isMuted);
        button.innerText = this.isMuted ? "🔇" : "🔊";
        Object.values(this.sounds).forEach(sound => sound.muted = this.isMuted);
    }

showIntroScreen() {
  this.container.innerHTML = Templates.introScreen(
    this.quiz.quizTitle, this.quiz.quizDescription, this.quiz.quizImage
  );
  document.getElementById('start-btn').addEventListener('click', async () => {
    try {
      await this.quiz.startAttempt();   // spend tokens + get attempt_id
      this.startQuiz();
    } catch (e) {
      this.showError(e.message || 'Neuspešno pokretanje kviza.');
    }
  });
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
            question, this.quiz.currentStep, this.quiz.quizData.length, this.quiz.timePerQuestion
        );

        const answersContainer = document.getElementById('answers');
        question.answers.forEach(answer => {
            const button = document.createElement('button');
            button.innerText = answer;
            button.classList.add('cs-answer-btn');
            button.addEventListener('click', () => this.checkAnswer(button, answer));
            answersContainer.appendChild(button);
        });

        this.startTimer();
    }


startTimer() {
  clearInterval(this.timer);
  const timerDisplay = document.getElementById("timer");
  const progressRing = document.querySelector(".cs-progress-ring-fill");
  let timeLeft = this.quiz.timePerQuestion;
  const maxTime = this.quiz.timePerQuestion;

  const radius = 55, circumference = 2 * Math.PI * radius;
  if (progressRing) {
    progressRing.style.strokeDasharray = `${circumference}`;
    progressRing.style.strokeDashoffset = 0;
  }

  this.timer = setInterval(() => {
    timeLeft--;
    if (timerDisplay) timerDisplay.innerText = timeLeft;
    if (progressRing) {
      progressRing.style.strokeDashoffset = ((maxTime - timeLeft) / maxTime) * circumference;
      if (timeLeft <= maxTime / 2) progressRing.style.stroke = "orange";
      if (timeLeft <= 3) progressRing.style.stroke = "red";
    }
    if (timeLeft <= 0) { clearInterval(this.timer); this.markTimeoutAsWrong(); }
  }, 1000);
}

    disableAllButtons() {
        document.querySelectorAll('.cs-answer-btn').forEach(btn => btn.disabled = true);
    }

    highlightCorrectAnswer(correctAnswer) {
        document.querySelectorAll('.cs-answer-btn').forEach(btn => {
            if (btn.innerText === correctAnswer) btn.classList.add('cs-correct');
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
            isCorrect: false
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
        button.classList.add('cs-correct');
        this.sounds.correct.play();
        this.quiz.score++;
    } else {
        button.classList.add('cs-wrong');
        this.sounds.wrong.play();
        this.highlightCorrectAnswer(correctAnswer); // ✅ Now highlights the actual correct answer
    }

    this.quiz.userAnswers.push({
        question: question.title,
        selected: selectedAnswer,
        correct: correctAnswer, // ✅ Ensure correct answer is properly stored
        isCorrect
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
    let countdownTime = 2; // ✅ Start countdown from 3 seconds
    this.container.innerHTML = Templates.nextQuestionScreen(
        this.quiz.currentStep + 1, 
        this.quiz.quizData.length,
        countdownTime
    );

    const countdownDisplay = document.getElementById("countdownTimer");
    const progressRing = document.querySelector(".cs-progress-ring-fill");
    const maxTime = countdownTime;
    const circumference = 2 * Math.PI * 55;

    progressRing.style.strokeDasharray = circumference;
    progressRing.style.strokeDashoffset = 0;

    let countdownTimer = setInterval(() => {
        countdownTime--;
        countdownDisplay.innerText = countdownTime;

        // ✅ Update progress ring (keeps shrinking)
        progressRing.style.strokeDashoffset = ((maxTime - countdownTime) / maxTime) * circumference;

        if (countdownTime <= 0) {
            clearInterval(countdownTimer);
            this.showQuestion(); // ✅ Move to next question after countdown
        }
    }, 1000);
}

    async showSummary() {
  try { await this.quiz.submitAttempt(); } catch (e) { console.error(e); }
  this.container.innerHTML = Templates.summaryScreen(this.quiz.score, this.quiz.quizData.length);
  this.sounds.background.pause();
}
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.cs-quiz-card').forEach(card => {
    const btnWrap = card.querySelector('.cs-quiz-button');
    if (!btnWrap) return;

    const anchor = btnWrap.querySelector('a') || btnWrap;

    // Prefer data-quiz-id on the card if you can add it in Elementor (Advanced → Attributes → data-quiz-id = {{ID}})
    const idFromData = card.dataset.quizId;
    const idFromHidden = card.querySelector('.cs-quiz-id')?.textContent?.trim();
    const idFromClass = (card.className.match(/post-(\d+)/) || [])[1];
    const quizId = idFromData || idFromHidden || idFromClass;

    if (!quizId) {
      console.error('Quiz ID not found for card:', card);
      return;
    }

    anchor.addEventListener('click', (e) => {
      e.preventDefault();            // prevent href="#" jump
      if (anchor.classList.contains('is-busy')) return; // guard double click
      anchor.classList.add('is-busy');

      // close any running quiz instance
      if (window.currentQuiz?.closeQuiz) window.currentQuiz.closeQuiz();

      window.currentQuiz = new Quiz(quizSettings.apiUrl, quizId);

      setTimeout(() => anchor.classList.remove('is-busy'), 800);
    });
  });
});


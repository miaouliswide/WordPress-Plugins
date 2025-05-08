jQuery(document).ready(function($) {
    $('.kadence-faq-question').on('click', function() {
        const $this = $(this);
        const $answer = $this.next('.kadence-faq-answer');
        const isExpanded = $this.attr('aria-expanded') === 'true';
        
        // Toggle current answer
        if (!isExpanded) {
            $this.attr('aria-expanded', 'true');
            $answer.slideDown(300).addClass('active');
        } else {
            $this.attr('aria-expanded', 'false');
            $answer.slideUp(300).removeClass('active');
        }
    });
});
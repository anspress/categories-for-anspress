<?php dynamic_sidebar( 'ap-top' ); ?>
<div class="row">
	<div id="ap-lists" class="<?php echo is_active_sidebar( 'ap-category' ) && is_anspress() ? 'col-md-9' : 'col-md-12' ?>">
		<div class="ap-taxo-detail">
			<h2 class="entry-title"><?php echo $question_category->name; ?> <span class="ap-tax-item-count"><?php printf( _n('1 Question', '%s Questions', $question_category->count, 'ap'),  $question_category->count); ?></span></h2>
			<?php if($question_category->description !=''): ?>
				<p class="ap-taxo-description"><?php echo $question_category->description; ?></p>
			<?php endif; ?>

			<?php ap_subscribe_btn_html($question_category->term_id, 'term'); ?>

			<?php
			$sub_cat_count = count(get_term_children( $question_category->term_id, 'question_category' ));

			if($sub_cat_count >0){
				echo '<div class="ap-term-sub">';
				echo '<div class="sub-taxo-label">' .$sub_cat_count.' '.__('Sub Categories', 'ap') .'</div>';
				ap_sub_category_list($question_category->term_id);
				echo '</div>';
			}
			?>
		</div>
		<?php ap_get_template_part('list-head'); ?>
		<?php if ( ap_have_questions() ) : ?>
			<div class="ap-questions">
				<?php

				/* Start the Loop */
				while ( ap_questions() ) : ap_the_question();
				global $post;
				include(ap_get_theme_location('content-list.php'));
				endwhile;
				?>
			</div>
			<?php ap_questions_the_pagination(); ?>
			<?php
			else : 
				include(ap_get_theme_location('content-none.php'));
			endif; 
			?>	
		</div>
		
		<?php if ( is_active_sidebar( 'ap-category' ) && is_anspress()){ ?>
		<div class="ap-question-right col-md-3">
			<?php dynamic_sidebar( 'ap-category' ); ?>
		</div>
		<?php } ?>
	</div>
